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
    protected $pageNumber = 1;
    protected $minPrice = 10.00;
    protected $maxPrice = 70.00;
    protected $feedbackScoreMin = 300;
    protected $feedbackScoreMax = 999999999;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $params)
    {
        //Log::info('[Ebay-FindItemsAdvanced] Params get: ' . json_encode($params, 256));
        $this->userId = $params['userId'];
        $this->pageNumber = $params['pageNumber'];
        $this->keywords = $params['keywords'];
        $this->feedbackScoreMin = (integer)$params['feedbackScoreMin'];
        $this->feedbackScoreMax = (integer)$params['feedbackScoreMax'];
        $this->minPrice = $params['minPrice'];
        $this->maxPrice = $params['maxPrice'];

    }

    /**
     * Execute the job.
     * Получаем список товаров по ключевому слову
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
                'SECURITY-APPNAME' => env('SECURITY_APPNAME', ''),
                'RESPONSE-DATA-FORMAT' => 'json',
                'REST-PAYLOAD' => 'true',
                'paginationInput.entriesPerPage' => '100',
                'paginationInput.pageNumber' => $this->pageNumber,
                'keywords' => $this->keywords,
                'itemFilter(0).name' => 'LocatedIn',
                'itemFilter(0).value' => 'US',
                'itemFilter(1).name' => 'MinPrice',
                'itemFilter(1).value' => $this->minPrice,
                'itemFilter(1).paramName' => 'Currency',
                'itemFilter(1).paramValue' => 'USD',
                'itemFilter(2).name' => 'MaxPrice',
                'itemFilter(2).value' => $this->maxPrice,
                'itemFilter(2).paramName' => 'Currency',
                'itemFilter(2).paramValue' => 'USD',
                'itemFilter(3).name' => 'FreeShippingOnly',
                'itemFilter(3).value' => 'true',
                'itemFilter(4).name' => 'Condition',
                'itemFilter(4).value' => '1000',
                'itemFilter(5).name' => 'MinQuantity',
                'itemFilter(5).value' => '3',
                'itemFilter(6).name' => 'FeedbackScoreMin',
                'itemFilter(6).value' => $this->feedbackScoreMin,
                'itemFilter(7).name' => 'FeedbackScoreMax',
                'itemFilter(7).value' => $this->feedbackScoreMax,
                'itemFilter(8).name' => 'positiveFeedbackPercent',
                'itemFilter(8).value' => '99.0',
                'itemFilter(9).name' => 'ReturnsAcceptedOnly',
                'itemFilter(9).value' => 'true',
                'itemFilter(10).name' => 'HideDuplicateItems',
                'itemFilter(10).value' => 'true',
                'itemFilter(11).name' => 'ListingType',
                'itemFilter(11).value' => 'FixedPrice',
                'outputSelector(0)' => 'SellerInfo',
                'outputSelector(1)' => 'GalleryInfo',
            )
        ));
        $result = $response->getBody()->getContents();

        $result = json_decode($result);
        $ar = $result->findItemsAdvancedResponse[0];
        $paginationOutput = $ar->paginationOutput[0];
        $pageNumber = $paginationOutput->pageNumber[0]; // Номер страницы
        $entriesPerPage = $paginationOutput->entriesPerPage[0]; //Количество товаров на странице
        $totalPages = $paginationOutput->totalPages[0]; //Всего страниц
        $totalEntries = $paginationOutput->totalEntries[0]; // Всего товаров

       // Log::info('[Ebay-FindItemsAdvanced] entriesPerPage: ' . $entriesPerPage);

        // Записываем ключевое слово запроса в бд
        $keyword = Keyword::updateOrCreate(
            [
                'name' => $this->keywords,
                'min_price' => $this->minPrice,
                'max_price' => $this->maxPrice,
                'feedback_score_min' => $this->feedbackScoreMin,
                'feedback_score_max' => $this->feedbackScoreMax
            ],
            [
                'total_products' => $totalEntries,
                'total_pages' => $totalPages,
                'parsed_pages' => $pageNumber
            ]
        );
        $keyword->users()->syncWithoutDetaching([$this->userId]);

        // Если по нашему запросу больше оодной страницы товаров, созадем задачу на следующую страницу
        if ($pageNumber < $totalPages && $pageNumber < 100) {
            $params = [
                'userId'            => $this->userId,
                'keywords'          => $this->keywords,
                'pageNumber'        => $this->pageNumber + 1,
                'minPrice'          => $this->minPrice,
                'maxPrice'          => $this->maxPrice,
                'feedbackScoreMin'  => $this->feedbackScoreMin,
                'feedbackScoreMax'  => $this->feedbackScoreMax,
            ];
            dispatch(new \App\Jobs\Ebay\EbayFindItemsAdvanced($params))->onQueue('findItems');
        }

        // Перебираем товары из запроса Макс 100шт.
        foreach ($ar->searchResult[0]->item as $key => $item) {
            //Log::info('[Ebay-FindItemsAdvanced] Key: '.$key.' Item: '.json_encode($item,256));
            $productUrl = $item->viewItemURL[0];
            // Проверяем, является ли товар Вариацией
            if (strpos($item->viewItemURL[0], '?var=') !== false &&
                strpos($item->viewItemURL[0], '?var=0') === false) {
                $productUrlVariation = explode('?var=0', $item->viewItemURL[0]);
                $productUrl = $productUrlVariation[0];
            }

            $sellerInfo = $item->sellerInfo[0];
            // Записываем информацию о продавце
            // todo updateOrCreate
            if (is_null($seller = Seller::where('user_name', $sellerInfo->sellerUserName[0])->first())) {
                $seller = Seller::create([
                    'user_name' =>  $sellerInfo->sellerUserName[0],
                    'feedback_score' =>  $sellerInfo->feedbackScore[0],
                    'positive_feedback_percent' =>  $sellerInfo->positiveFeedbackPercent[0],
                    'feedback_rating_star' =>  $sellerInfo->feedbackRatingStar[0],
                    'top_rated_seller' =>  $sellerInfo->topRatedSeller[0],
                ]);
                dispatch(new \App\Jobs\Ebay\EbayGetCustomerInfo($sellerInfo->sellerUserName[0]))->onConnection('redis');
            } else {
                $seller = Seller::where('user_name', $sellerInfo->sellerUserName[0])->first();
                $seller->feedback_score = $sellerInfo->feedbackScore[0];
                $seller->positive_feedback_percent = $sellerInfo->positiveFeedbackPercent[0];
                $seller->top_rated_seller = $sellerInfo->topRatedSeller[0];
                $seller->save();
                $seller->refresh();
            }

            $itemId = $item->itemId[0];
            // Записываем информацию о товаре
            if (is_null($product = Product::where('item_id', $itemId)->first())) {
                $product = Product::create([
                        'item_id' => $itemId,
                        'seller_id' => $seller->id,
                        'title' => $item->title[0],
                        'global_id' => $item->globalId[0],
                        'category_id' => $item->primaryCategory[0]->categoryId[0],
                        'item_url' => $productUrl,
                        'location' => $item->location[0],
                        'country' => $item->country[0],
                        'condition_name' => $item->condition[0]->conditionDisplayName[0],
                        'variation' => $item->isMultiVariationListing[0] === 'true' ? true : false,
                        'handling_time' => $item->shippingInfo[0]->handlingTime[0],
                    ]
                );
                if ($product->id % 20 == 0) {
                    $numMin = $product->id - 19;
                    $itemIds = range($numMin, $product->id);
                    dispatch(new \App\Jobs\Ebay\EbayGetMultipleItems($itemIds))->onQueue('multipleItems');
                }
                dispatch(new \App\Jobs\Ebay\EbayGetProductInfo($product->id))->onConnection('redis');
                dispatch(new \App\Jobs\Ebay\EbayGetShippingCosts($product->id, $product->item_id))->onQueue('shippingCosts');
            }

            $product->keywords()->syncWithoutDetaching([$keyword->id]);
        }
    }
}
