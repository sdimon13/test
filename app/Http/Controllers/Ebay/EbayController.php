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
        return Product::where('seller_id', 1)->with('photos', 'shipping')->get()->toArray();
    }

    public function test()
    {
        $client = new Client();
        $url = 'http://open.api.ebay.com/shopping';
        $response = $client->get($url, array(
            'query' => array(
                'callname' => 'GetShippingCosts',
                'responseencoding' => 'JSON',
                'appid' => 'DmitriyS-SDKOA-PRD-769dbd521-3986ee4d',
                'siteid' => '0',
                'version' => '869',
                'ItemID' => '302927859899',
                'DestinationCountryCode' => 'US',
                'DestinationPostalCode' => '20189',
                'IncludeDetails' => 'true',
                'QuantitySold' => '1',
            )
        ));
        $result = $response->getBody()->getContents();
        $result = json_decode($result);

        foreach ($result->ShippingDetails->ShippingServiceOption as $shipping) {
            $details = new Shipping();
            $details->product_id = 1;
            $details->name = $shipping->ShippingServiceName;
            $details->cost = $shipping->ShippingServiceCost->Value;
            $details->additional_cost = $shipping->ShippingServiceAdditionalCost->Value;
            $details->priority = $shipping->ShippingServicePriority;
            $details->time_min = $shipping->ShippingTimeMin;
            $details->time_max = $shipping->ShippingTimeMax;
            $details->save();
            print_r($details."<br/>");
        }
    }
}
