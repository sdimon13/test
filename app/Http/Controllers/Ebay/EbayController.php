<?php

namespace App\Http\Controllers\Ebay;


use App\Http\Controllers\Controller;
use App\Models\Ebay\Keyword;
use App\Models\Ebay\Photo;
use App\Models\Ebay\Product;
use App\Models\Ebay\Seller;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class EbayController extends Controller
{
    public function index()
    {
        return view('ebay/home');
    }

    public function findItemsAdvanced()
    {
        $keywords = 'stool';
        $pageNumber = 1;

        $userId = \Auth::user()->id;
        $params = [
            'userId' => $userId,
            'keywords' => $keywords,
            'pageNumber' => $pageNumber,
        ];
        $keyword = Keyword::firstOrCreate(
            ['name' => $keywords]
        );
        $keyword->users()->syncWithoutDetaching([$userId]);
        dispatch(new \App\Jobs\Ebay\EbayFindItemsAdvanced($params));
    }

    public function sellers()
    {
        return view('ebay/sellers', [

            'sellers' => Seller::where('positive_feedback_percent', 99.5)->withCount('products')->paginate(10)

        ]);
    }

    public function products()
    {
        return Product::with('photos', 'shippings')->get()->toArray();
    }

    public function test()
    {
        $itemIds = [2,5,8];
        $itemIds = array_diff($itemIds, array('', NULL, false));
        $productIds = Product::find($itemIds)->implode('item_id', ',');
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
                $product = Product::where('item_id', $item->ItemID)->first();
                $product->description = $item->Description;
                $product->quantity = $item->Quantity;
                $product->quantity_sold = $item->QuantitySold;
                $product->sku = $item->SKU ?? null;
                $product->save();
                $productTitle = $product->title;
                if (isset($item->Variations) && count($item->Variations->Variation)) {
                    foreach ($item->Variations->Variation as $variation) {
                        $variationName = '(';
                        foreach ($variation->VariationSpecifics->NameValueList as $key => $value) {
                            if ($key != 0) {
                                $variationName .= ', ';
                            }
                            $variationName .= $value->Name . ':';
                            $variationName .= $value->Value[0];
                            foreach ($item->Variations->Pictures as $variationPictures) {
                                if ($variationPictures->VariationSpecificName == $value->Name) {
                                    foreach ($variationPictures->VariationSpecificPictureSet as $pictureSet) {
                                        if ($pictureSet->VariationSpecificValue == $value->Value[0]) {
                                            $pictures = $pictureSet->PictureURL;
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
        } else {
            info('[Ebay-GetMultipleItems] ERROR Items not found : '.json_encode($productIds, 256));
        }

        //dispatch(new \App\Jobs\Ebay\EbayGetProductInfo(1));
    }
}
