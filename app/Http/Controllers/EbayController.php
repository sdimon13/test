<?php

namespace App\Http\Controllers;

use App\Photo;
use App\Product;
use App\Seller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Guzzle\Http\Exception\ClientErrorResponseException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\DomCrawler\Crawler;

class EbayController extends Controller
{
    public function index()
    {
        return Seller::all()->where('positive_feedback_percent', '>', 99.5);
    }

    public function add()
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
                'paginationInput.pageNumber' => '1',
                'keywords' => 'toy',
                'itemFilter(0).name' => 'MinPrice',
                'itemFilter(0).value' => '10.00',
                'itemFilter(0).paramName' => 'Currency',
                'itemFilter(0).paramValue' => 'USD',
                'itemFilter(1).name' => 'MaxPrice',
                'itemFilter(1).value' => '60.00',
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
                'itemFilter(6).value' => '99.6',
                'itemFilter(7).name' => 'ReturnsAcceptedOnly',
                'itemFilter(7).value' => 'true',
                'outputSelector(0)' => 'SellerInfo',
                'outputSelector(1)' => 'GalleryInfo',
                )
        ));
        $result = $response->getBody()->getContents();

        $result = json_decode($result);
        $AR = $result->findItemsAdvancedResponse;

        foreach($AR[0]->searchResult[0]->item as $item) {

           $sellerInfo = $item->sellerInfo[0];  //  Информация о продавце
           $seller = Seller::where('user_name', $sellerInfo->sellerUserName[0])->get();
           if(count($seller)) {
               continue;
           }

          /* $seller = new Seller();
           $seller->user_name = $sellerInfo->sellerUserName[0];
           $seller->feedback_score = $sellerInfo->feedbackScore[0];
           $seller->positive_feedback_percent = $sellerInfo->positiveFeedbackPercent[0];
           $seller->feedback_rating_star = $sellerInfo->feedbackRatingStar[0];
           $seller->top_rated_seller = $sellerInfo->topRatedSeller[0];
           $seller->save();*/

           $product = new Product();
           $product->item_id = $item->itemId[0];
           $product->seller_id = 1;
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

           $photo = new Photo();
           $photo->product_id = $product->id;
           $photo->large = $item->galleryInfoContainer[0]->galleryURL[0]->__value__;
           $photo->medium = $item->galleryInfoContainer[0]->galleryURL[1]->__value__;
           $photo->small = $item->galleryInfoContainer[0]->galleryURL[2]->__value__;
           $photo->save();
           //print_r($product);
           exit;

        }
    }

    public function getUserInfo($seller)
    {
        $client = new Client();
        $response = $client->get('https://www.ebay.com/usr/ddarlingshop');
        $html = $response->getBody()->getContents();

        $crawler = new Crawler(null, 'https://www.ebay.com/usr/'.$seller);

        $crawler->addHtmlContent($html, 'UTF-8');

        // Get title text.
        $date_reg = $crawler->filter('#member_info .info')->text();
        $country = $crawler->filter('#member_info .mem_loc')->text();
    }
}
