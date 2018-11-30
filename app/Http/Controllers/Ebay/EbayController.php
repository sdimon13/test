<?php

namespace App\Http\Controllers\Ebay;


use App\Http\Controllers\Controller;
use App\Models\Ebay\Product;
use App\Models\Ebay\Seller;

class EbayController extends Controller
{
    public function index()
    {
       //
    }

    public function findItemsAdvanced()
    {
        $keywords = 'toy';
        $pageNumber = 1;

        dispatch(new \App\Jobs\Ebay\EbayFindItemsAdvanced($keywords, $pageNumber));
    }

    public function sellers()
    {
       return Seller::where('positive_feedback_percent', 99.5)->withCount('products')->get()->toArray();
    }

    public function products()
    {
        return Product::where('seller_id', 1)->with('photos')->get()->toArray();
    }
}
