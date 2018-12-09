<?php

namespace App\Jobs\Ebay;

use App\Models\Ebay\Keyword;
use App\Models\Ebay\Photo;
use App\Models\Ebay\Product;
use App\Models\Ebay\Seller;
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

    protected $userId;
    protected $keywords;
    protected $pageNumber;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $params)
    {
        Log::info('[Ebay-FindItemsAdvanced] Params: '.json_encode($params,256));
        $this->userId=$params['userId'];
        $this->pageNumber=$params['pageNumber'];
        $this->keywords=$params['keywords'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
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
                'itemFilter(0).name' => 'LocatedIn',
                'itemFilter(0).value' => 'US',
                'itemFilter(1).name' => 'MinPrice',
                'itemFilter(1).value' => '35.00',
                'itemFilter(1).paramName' => 'Currency',
                'itemFilter(1).paramValue' => 'USD',
                'itemFilter(2).name' => 'MaxPrice',
                'itemFilter(2).value' => '60.00',
                'itemFilter(2).paramName' => 'Currency',
                'itemFilter(2).paramValue' => 'USD',
                'itemFilter(3).name' => 'FreeShippingOnly',
                'itemFilter(3).value' => 'true',
                'itemFilter(4).name' => 'Condition',
                'itemFilter(4).value' => '1000',
                'itemFilter(5).name' => 'MinQuantity',
                'itemFilter(5).value' => '3',
                'itemFilter(6).name' => 'FeedbackScoreMin',
                'itemFilter(6).value' => '300',
                'itemFilter(7).name' => 'positiveFeedbackPercent',
                'itemFilter(7).value' => '99.0',
                'itemFilter(8).name' => 'ReturnsAcceptedOnly',
                'itemFilter(8).value' => 'true',
                'itemFilter(9).name' => 'HideDuplicateItems',
                'itemFilter(9).value' => 'true',
                'itemFilter(10).name' => 'ListingType',
                'itemFilter(10).value' => 'FixedPrice',
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

        Log::info('[Ebay-FindItemsAdvanced] entriesPerPage: '.$entriesPerPage);

        $keyword = Keyword::updateOrCreate(
            ['name' => $this->keywords],
            ['total_products' => $totalEntries]
        );

        if ($pageNumber < $totalPages && $pageNumber < 100) {
            $params = [
                'userId' => $this->userId,
                'keywords' => $this->keywords,
                'pageNumber' => $this->pageNumber+1,
            ];
            dispatch(new \App\Jobs\Ebay\EbayFindItemsAdvanced($params));
        }

        foreach($ar->searchResult[0]->item as $key => $item) {
            Log::info('[Ebay-FindItemsAdvanced] Key: '.$key.' Item: '.json_encode($item,256));
            $productUrl = $item->viewItemURL[0];
            if (strpos($item->viewItemURL[0], '?var=' ) !== false && strpos($item->viewItemURL[0], '?var=0' ) === false) {
               $productUrlVariation = explode('?var=0', $item->viewItemURL[0]);
               $productUrl = $productUrlVariation[0];
            }

            $sellerInfo = $item->sellerInfo[0];  //  Информация о продавце
            if (Seller::where('user_name', $sellerInfo->sellerUserName[0])->count()) {
                $seller = Seller::where('user_name', $sellerInfo->sellerUserName[0])->first();
                $seller->feedback_score = $sellerInfo->feedbackScore[0];
                $seller->positive_feedback_percent = $sellerInfo->positiveFeedbackPercent[0];
                $seller->top_rated_seller = $sellerInfo->topRatedSeller[0];
                $seller->save();
            } else {
                $seller = new Seller();
                $seller->user_name = $sellerInfo->sellerUserName[0];
                $seller->feedback_score = $sellerInfo->feedbackScore[0];
                $seller->positive_feedback_percent = $sellerInfo->positiveFeedbackPercent[0];
                $seller->feedback_rating_star = $sellerInfo->feedbackRatingStar[0];
                $seller->top_rated_seller = $sellerInfo->topRatedSeller[0];
                $seller->save();
                $seller->refresh();
                $seller->users()->syncWithoutDetaching([$this->userId]);
                dispatch(new \App\Jobs\Ebay\EbayGetCustomerInfo($seller->user_name))->onConnection('redis');
            }

            $itemId = $item->itemId[0];
            if(Product::where('item_id', $itemId)->count()) {
                Log::info('[Ebay-FindItemsAdvanced] Product update: '.$itemId);
                $product = Product::where('item_id', $itemId)->first();
                $product->title = $item->title[0];
                //$product->price = $item->sellingStatus[0]->convertedCurrentPrice[0]->__value__;
                $product->category_id = $item->primaryCategory[0]->categoryId[0];
                $product->item_url = $productUrl;
                $product->shipping_cost = $item->shippingInfo[0]->shippingServiceCost[0]->__value__;
                $product->save();
                $product->refresh();
            } else {
                Log::info('[Ebay-FindItemsAdvanced] Product new: '.$itemId);
                $product = new Product();
                $product->item_id = $itemId;
                $product->seller_id = $seller->id;
                $product->title = $item->title[0];
                $product->price = $item->sellingStatus[0]->convertedCurrentPrice[0]->__value__;
                $product->global_id = $item->globalId[0];
                $product->category_id = $item->primaryCategory[0]->categoryId[0];
                $product->item_url = $productUrl;
                $product->location = $item->location[0];
                $product->country = $item->country[0];
                $product->shipping_cost = $item->shippingInfo[0]->shippingServiceCost[0]->__value__;
                $product->condition_name = $item->condition[0]->conditionDisplayName[0];
                $product->variation = $item->isMultiVariationListing[0] === 'true'? true: false;
                $product->main_photo = 'http://galleryplus.ebayimg.com/ws/web/'.$product->item_id.'_1_1_1.jpg';
                $product->save();
                $product->refresh();
                if($product->id % 20 == 0) {
                    $numMin = $product->id - 19;
                    $itemIds = range($numMin, $product->id);
                    dispatch(new \App\Jobs\Ebay\EbayGetMultipleItems($itemIds));
                }
                dispatch(new \App\Jobs\Ebay\EbayGetProductInfo($product->id))->onConnection('redis');
                dispatch(new \App\Jobs\Ebay\EbayGetShippingCosts($product->id, $product->item_id));
            }
            $product->keywords()->syncWithoutDetaching([$keyword->id]);
        }
    }
}
