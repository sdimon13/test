<?php

namespace App\Jobs\Ebay;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Guzzle\Http\Exception\ClientErrorResponseException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\BadResponseException;

class EbayFindItemsAdvanced implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    protected $keywords;
    protected $pageNumber;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($keywords, $pageNumber)
    {
        $this->keywords = $keywords;
        $this->pageNumber = $pageNumber;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Keyword: '.$this->keywords.' PageNumber: '.$this->pageNumber);
        $client = new Client();
        $url = 'http://svcs.ebay.com/services/search/FindingService/v1';
        $response = $client->get($url, array(
            'query' => array(
                'OPERATION-NAME' => 'findItemsAdvanced',
                'SERVICE-VERSION' => '1.13.0',
                'SECURITY-APPNAME' => 'DmitriyS-SDKOA-PRD-769dbd521-3986ee4d',
                'RESPONSE-DATA-FORMAT' => 'json',
                'REST-PAYLOAD' => 'true',
                'paginationInput.entriesPerPage' => '100',
                'paginationInput.pageNumber' => $this->pageNumber,
                'keywords' => $this->keywords,
                'itemFilter(0).name' => 'MinPrice',
                'itemFilter(0).value' => '10.00',
                'itemFilter(0).paramName' => 'Currency',
                'itemFilter(0).paramValue' => 'USD',
                'itemFilter(1).name' => 'MaxPrice',
                'itemFilter(1).value' => '70.00',
                'itemFilter(1).paramName' => 'Currency',
                'itemFilter(1).paramValue' => 'USD',
                'itemFilter(2).name' => 'FreeShippingOnly',
                'itemFilter(2).value' => 'true',
                'itemFilter(3).name' => 'Condition',
                'itemFilter(3).value' => '1000',
                'itemFilter(4).name' => 'MinQuantity',
                'itemFilter(4).value' => '3',
                'itemFilter(5).name' => 'FeedbackScoreMin',
                'itemFilter(5).value' => '300',
                'itemFilter(6).name' => 'positiveFeedbackPercent',
                'itemFilter(6).value' => '99.0',
                'itemFilter(7).name' => 'ReturnsAcceptedOnly',
                'itemFilter(7).value' => 'true',
                'outputSelector(0)' => 'SellerInfo',
                'outputSelector(1)' => 'GalleryInfo',
            )
        ));
        $result = $response->getBody()->getContents();

        $result = json_decode($result);
        $ar= $result->findItemsAdvancedResponse[0];
        $paginationOutput = $ar->paginationOutput[0];
        $pageNumber = $paginationOutput->pageNumber[0]; // Номер страницы
        $entriesPerPage = $paginationOutput->entriesPerPage[0]; //Количество товаров на странице
        $totalPages = $paginationOutput->totalPages[0]; //Всего страниц
        $totalEntries = $paginationOutput->totalEntries[0]; // Всего товаров
        if ($pageNumber < $totalPages) {
            dispatch(new \App\Jobs\Ebay\EbayFindItemsAdvanced($this->keywords, $this->pageNumber+1));
        }

        foreach($ar->searchResult[0]->item as $item) {

            $sellerInfo = $item->sellerInfo[0];  //  Информация о продавце
            $seller = Seller::where('user_name', $sellerInfo->sellerUserName[0])->get();
            if (count($seller)) {
                $seller = Seller::where('user_name', $sellerInfo->sellerUserName[0])
                    ->update([
                        'feedback_score' => $sellerInfo->feedbackScore[0],
                        'positive_feedback_percent' => $sellerInfo->positiveFeedbackPercent[0],
                        'top_rated_seller' => $sellerInfo->topRatedSeller[0],
                    ]);
            } else {
                $seller = new Seller();
                $seller->user_name = $sellerInfo->sellerUserName[0];
                $seller->feedback_score = $sellerInfo->feedbackScore[0];
                $seller->positive_feedback_percent = $sellerInfo->positiveFeedbackPercent[0];
                $seller->feedback_rating_star = $sellerInfo->feedbackRatingStar[0];
                $seller->top_rated_seller = $sellerInfo->topRatedSeller[0];
                $seller->save();
                dispatch(new \App\Jobs\Ebay\EbayGetCustomerInfo($seller->user_name));
            }

            $product = Product::where('item_id', $item->itemId[0])->get();
            if(count($product)) {
                $product = Product::where('item_id', $item->itemId[0])->update([
                    'title' => $item->title[0],
                    'price' => $item->sellingStatus[0]->convertedCurrentPrice[0]->__value__,
                    'category_id' => $item->primaryCategory[0]->categoryId[0],
                    'item_url' => $item->viewItemURL[0],
                    'shipping_cost' => $item->shippingInfo[0]->shippingServiceCost[0]->__value__,
                ]);
            } else {
                $product = new Product();
                $seller = Seller::where('user_name', $sellerInfo->sellerUserName[0])->first();
                $product->item_id = $item->itemId[0];
                $product->seller_id = $seller->id;
                $product->title = $item->title[0];
                $product->price = $item->sellingStatus[0]->convertedCurrentPrice[0]->__value__;
                $product->global_id = $item->globalId[0];
                $product->category_id = $item->primaryCategory[0]->categoryId[0];
                $product->item_url = $item->viewItemURL[0];
                $product->location = $item->location[0];
                $product->country = $item->country[0];
                $product->shipping_cost = $item->shippingInfo[0]->shippingServiceCost[0]->__value__;
                $product->condition_name = $item->condition[0]->conditionDisplayName[0];
                $product->save();
                $product->refresh();
            }

            $product = Product::where('item_id', $item->itemId[0])->first();
            $photo = Photo::where('product_id', $product->id)->get();
            if(count($photo)) {
                $photo = Photo::where('product_id', $product->id)->first();
            } else {
                $photo = new Photo();
            }
            $photo->product_id = $product->id;
            $photo->large = $item->galleryInfoContainer[0]->galleryURL[0]->__value__;
            $photo->medium = $item->galleryInfoContainer[0]->galleryURL[1]->__value__;
            $photo->small = $item->galleryInfoContainer[0]->galleryURL[2]->__value__;
            $photo->save();
        }
    }
}
