<?php

namespace App\Http\Controllers\Ebay;


use App\Http\Controllers\Controller;
use App\Models\Ebay\Product;
use App\Models\Ebay\Seller;
use App\Models\Ebay\Shipping;
use GuzzleHttp\Client;

class EbayController extends Controller
{
    public function index()
    {
       return \Auth::user()->id;
    }

    public function findItemsAdvanced()
    {
        $keywords = 'toy';
        $pageNumber = 1;

        $userId = \Auth::user()->id;
        $params = [
            'userId' => 1,
            'keywords' => $keywords,
            'pageNumber' => $pageNumber,
        ];
        dispatch(new \App\Jobs\Ebay\EbayFindItemsAdvanced($params));
    }

    public function sellers()
    {
       return Seller::where('positive_feedback_percent', 99.5)->withCount('products')->get()->toArray();
    }

    public function products()
    {
        return Product::where('seller_id', 1)->with('photos', 'shipping')->get()->toArray();
    }

    public function test()
    {
        dispatch(new \App\Jobs\Ebay\EbayGetMultipleItems('273547548963'));
    }
}
