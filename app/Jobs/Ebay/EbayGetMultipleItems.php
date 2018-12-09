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
     *
     * @return void
     */
    public function handle()
    {
        $this->itemIds = array_diff($this->itemIds, array('', NULL, false));
        $productIds = Product::whereNull('parent_id')->whereNotNull('item_id')->find($this->itemIds)->implode('item_id', ',');
        info('[Ebay-GetMultipleItems] Product ids: '.json_encode($productIds, 256));
        $client = new Client();
        $url = 'http://open.api.ebay.com/shopping';
        $response = $client->get($url, array(
            'query' => array(
                'callname' => 'GetMultipleItems',
                'responseencoding' => 'JSON',
                'appid' => 'DmitriyS-SDKOA-PRD-769dbd521-3986ee4d',
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
                info('[Ebay-GetMultipleItems] Product Item id: '.$item->ItemID);
                if (!Product::where('item_id', $item->ItemID)->count()) {
                    info("\n [Ebay-GetMultipleItems] Product Item id: ".$item->ItemID." not found \n");
                    continue;
                }
                $product = Product::where('item_id', $item->ItemID)->first();
                $product->description = $item->Description;
                $product->quantity = $item->Quantity;
                $product->quantity_sold = $item->QuantitySold;
                $product->sku = $item->SKU ?? null;
                $product->save();
                $productTitle = $product->title;
                $pictures =[];
                if (isset($item->Variations) && count($item->Variations->Variation)) {
                    foreach ($item->Variations->Variation as $variation) {
                        $variationName = '(';
                        foreach ($variation->VariationSpecifics->NameValueList as $key => $value) {
                            if ($key != 0) {
                                $variationName .= ', ';
                            }
                            $variationName .= $value->Name . ':';
                            $variationName .= $value->Value[0];
                            if (isset($item->Variations->Pictures) && count($item->Variations->Pictures)) {
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

                        if (Product::where('title', $variationName)->count()) {
                            $product = Product::where('title', $variationName)->update([
                                'price' => $variation->StartPrice->Value,
                                'quantity' => $variation->Quantity,
                                'quantity_sold' => $variation->SellingStatus->QuantitySold,
                            ]);
                        } else {
                            $product = Product::where('item_id', $item->ItemID)->first();
                            $child = new Product();
                            $child->parent_id = $product->item_id;
                            $child->seller_id = $product->seller_id;
                            $child->sku = $variation->SKU ?? null;
                            $child->title = $variationName;
                            $child->description = $product->description;
                            $child->price = $variation->StartPrice->Value;
                            $child->quantity = $variation->Quantity;
                            $child->quantity_sold = $variation->SellingStatus->QuantitySold;
                            $child->global_id = $product->global_id;
                            $child->category_id = $product->category_id;
                            $child->item_url = $product->item_url;
                            $child->location = $product->location;
                            $child->country = $product->country;
                            $child->shipping_cost = $product->shipping_cost;
                            $child->condition_name = $product->condition_name;
                            $child->variation = null;
                            $child->save();
                            $child->refresh();

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
        } else {
            info('[Ebay-GetMultipleItems] ERROR Items not found : '.json_encode($productIds, 256));
        }
    }
}
