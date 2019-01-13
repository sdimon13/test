<?php

namespace App\Jobs\Ebay;

use App\Models\Ebay\Photo;
use App\Models\Ebay\Product;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class EbayGetMultipleItems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $itemIds;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($itemIds)
    {
        $this->itemIds = $itemIds;
    }

    /**
     * Execute the job.
     * Получаем цену, кооичество и вариации по идентифиеатору товара ebay
     *
     * @return void
     */
    public function handle()
    {
        if (is_array($this->itemIds)) {
        $this->itemIds = array_diff($this->itemIds, array('', NULL, false));
        $productIds = Product::whereNull('parent_id')->whereNotNull('item_id')->find($this->itemIds)->implode('item_id', ',');
        } else {
        $productIds = $this->itemIds;
        }

        //info('[Ebay-GetMultipleItems] Product ids: '.json_encode($productIds, 256));
        $client = new Client();
        $url = 'http://open.api.ebay.com/shopping';
        $response = $client->get($url, array(
            'query' => array(
                'callname' => 'GetMultipleItems',
                'responseencoding' => 'JSON',
                'appid' => env('SECURITY_APPNAME'),
                'siteid' => '0',
                'version' => '967',
                'ItemID' => $productIds,
                'includeSelector' => 'Details, Variations, TextDescription',
            )
        ));
        $result = $response->getBody()->getContents();
        $result = json_decode($result);

        if (isset($result->Item)) {
            foreach ($result->Item as $item) {
                //info('[Ebay-GetMultipleItems] Product Item id: '.$item->ItemID);
                if (is_null($product = Product::where('item_id', $item->ItemID)->first())) {
                    //info("\n [Ebay-GetMultipleItems] Product Item id: ".$item->ItemID." not found \n");
                    continue;
                }
                $product = Product::where('item_id', $item->ItemID)->first();
                $product->description = $item->Description ?? null;
                $product->price = $item->ConvertedCurrentPrice->Value ?? null;
                $product->quantity = $item->Quantity;
                $product->quantity_sold = $item->QuantitySold;
                $product->save();
                $productTitle = $product->title;

                $pictures =[];

                // Проверяем наличие вариаций
                if (isset($item->Variations) && count($item->Variations->Variation)) {
                    foreach ($item->Variations->Variation as $variation) {
                        $variationName = '(';
                        foreach ($variation->VariationSpecifics->NameValueList as $key => $value) {
                            if ($key != 0) {
                                $variationName .= ', ';
                            }
                            $variationName .= $value->Name . ':';
                            $variationName .= $value->Value[0];
                            if (isset($item->Variations->Pictures)
                                && count($item->Variations->Pictures)) {
                                foreach ($item->Variations->Pictures as $variationPictures) {
                                    if ($variationPictures->VariationSpecificName == $value->Name) {

                                        foreach ($variationPictures->VariationSpecificPictureSet as $pictureSet) {
                                            if ($pictureSet->VariationSpecificValue == $value->Value[0]) {
                                                if (isset($pictureSet->PictureURL)) {
                                                    $pictures = $pictureSet->PictureURL;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $variationName = $productTitle . ' ' . $variationName . ')';
                        $childId = (int)gmp_strval(gmp_init(substr(md5($variationName),
                            0, 16), 16), 10);

                        // Обновляем / Добавляем вариации к товару
                        if (is_null($child = Product::where('item_id', $childId)->first())) {
                            $product = Product::where('item_id', $item->ItemID)->first();
                            $child = $product->replicate();
                            $child->item_id = $childId;
                            $child->parent_id = $product->item_id;
                            $child->title = $variationName;
                            $child->price = $variation->StartPrice->Value;
                            $child->quantity = $variation->Quantity;
                            $child->quantity_sold = $variation->SellingStatus->QuantitySold;
                            $child->variation = null;
                            $child->save();
                            $child->refresh();

                            $child->keywords()->syncWithoutDetaching([$product->keywords->first()->id]);

                            //info('count'.count($pictures));
                            if (count($pictures)) {
                                foreach ($pictures as $picture) {
                                    $photo = new Photo();
                                    $photo->product_id = $child->id;
                                    $photo->photo = $picture;
                                    $photo->save();
                                }
                            }
                        } else {
                            $product = Product::where('item_id', $childId)->update([
                                'price' => $variation->StartPrice->Value,
                                'quantity' => $variation->Quantity,
                                'quantity_sold' => $variation->SellingStatus->QuantitySold,
                            ]);
                            if ($child = Product::where('item_id', $childId)->doesntHave('photos')->count()) {
                                $child = Product::where('item_id', $childId)->first();
                                if (count($pictures)) {
                                    foreach ($pictures as $picture) {
                                        $photo = new Photo();
                                        $photo->product_id = $child->id;
                                        $photo->photo = $picture;
                                        $photo->save();
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            info('[Ebay-GetMultipleItems] ERROR Items not found : '.json_encode($productIds, 256));
        }
    }
}
