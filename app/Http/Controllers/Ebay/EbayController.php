<?php

namespace App\Http\Controllers\Ebay;


use App\Http\Controllers\Controller;
use App\Models\Ebay\Product;
use App\Models\Ebay\Seller;
use Illuminate\Http\Request;

class EbayController extends Controller
{
    public function index()
    {
        return view('ebay/home');
    }

    public function sellers(Request $request)
    {
        $seller = Seller::whereHas('products.keywords.users', function ($query) {
            $query->where('user_id', \Auth::user()->id);
        });

        if (!is_null($request->positive_feedback_percent)) {
            $seller->where('positive_feedback_percent', '>=', $request->positive_feedback_percent);
        }

        if (!is_null($request->feedback_score)) {
            $seller->where('feedback_score', '>=', $request->feedback_score);
        }

        if (!is_null($request->country)) {
            $seller->where('country', $request->country);
        }

        if (!is_null($request->keywords)) {
            $seller->whereHas('products.keywords', function ($query) use ($request) {
                    $query->where('name', $request->keywords);
            })
                ->withCount(['products' => function ($query) use ($request) {
                    $query->whereHas('keywords', function ($query) use ($request) {
                        $query->where('name', $request->keywords);
                    });
                }]);
        } else {
            $seller->withCount('products');
        }

        $seller->orderBy('products_count', 'Desk');

        return view('ebay/sellers', [
            'sellers' => $seller->paginate(10)->appends($_GET)
        ]);
    }

    public function products(Request $request)
    {
        $products = Product::whereHas('keywords.users', function ($query) use ($request) {
            $query->where('user_id', \Auth::user()->id);
        });

        if (!is_null($request->keywords)) {
            $products->whereHas('keywords', function ($query) use ($request) {
                $query->where('name', $request->keywords);
            });
        }

        if (!is_null($request->brand)) {
            $products->where('brand', $request->brand);
        }

        if (!is_null($request->min_price)) {
            $products->where('price', '>=', $request->min_price);
        }

        if (!is_null($request->max_price)) {
            $products->where('price', '<=', $request->max_price);
        }

        if (!is_null($request->quantity)) {
            $products->where('quantity', '>=', $request->quantity);
        }

        if (!is_null($request->quantity_sold)) {
            $products->where('quantity_sold', '>=', $request->quantity_sold);
        }

        if (!is_null($request->country)) {
            $products->where('country', $request->country);
        }

        if (!is_null($request->variation)) {
            $products->where('variation', $request->variation);
        }

        if (!is_null($request->handling_time)) {
            $products->where('handling_time', '<=', $request->handling_time);
        }

        if (!is_null($request->seller_id)) {
            $products->where('seller_id', '=', $request->seller_id);
        }

        if (!is_null($request->shippings_cost)) {
            $products->with(['shippings' => function ($query) use ($request) {
                $query->where('cost', '<=', $request->shippings_cost);
            }]);
        } else {
            $products->with('shippings');
        }

        if (!is_null($request->shippings_time_max)) {
            $products->with(['shippings' => function ($query) use ($request) {
                $query->where('time_max', '<=', $request->shippings_time_max);
            }]);
        } else {
            $products->with('shippings');
        }


        return view('ebay/products', [
            'products' =>$products->paginate(10)->appends($_GET)
        ]);
    }

    public function checkProductsWithoutPhoto()
    {
        $products = Product::whereNotNull('parent_id')->doesntHave('photos')->get()->unique()->chunk(20);
        print_r($products);
        foreach ($products as $product) {
            $itemIds = $product->implode('parent_id', ',');
            dispatch(new \App\Jobs\Ebay\EbayGetMultipleItems($itemIds))->onConnection('redis');
        }
    }

    public function checkSellersWithoutCoutry()
    {
        $sellers = Seller::whereNull('country')->pluck('user_name');
        foreach ($sellers as $userName) {
            dispatch(new \App\Jobs\Ebay\EbayGetCustomerInfo($userName))->onConnection('redis');
        }
    }

    public function test()
    {
        //todo brand for child
    }
}
