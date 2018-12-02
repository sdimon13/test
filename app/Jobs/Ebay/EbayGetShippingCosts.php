<?php

namespace App\Jobs\Ebay;

use App\Models\Ebay\Shipping;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class EbayGetShippingCosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    protected $id;
    protected $itemId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $itemId)
    {
        $this->id = $id;
        $this->itemId = $itemId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Item: '.$this->itemId);

        $client = new Client();
        $url = 'http://open.api.ebay.com/shopping';
        $response = $client->get($url, array(
            'query' => array(
                'callname' => 'GetShippingCosts',
                'responseencoding' => 'JSON',
                'appid' => 'DmitriyS-SDKOA-PRD-769dbd521-3986ee4d',
                'siteid' => '0',
                'version' => '869',
                'ItemID' => $this->itemId,
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
            $details->product_id = $this->id;
            $details->name = $shipping->ShippingServiceName;
            $details->cost = $shipping->ShippingServiceCost->Value;
            $details->additional_cost = $shipping->ShippingServiceAdditionalCost->Value;
            $details->priority = $shipping->ShippingServicePriority;
            $details->time_min = $shipping->ShippingTimeMin;
            $details->time_max = $shipping->ShippingTimeMax;
            $details->save();
        }

    }
}