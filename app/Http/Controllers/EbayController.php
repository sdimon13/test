<?php

namespace App\Http\Controllers;

use App\Seller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Guzzle\Http\Exception\ClientErrorResponseException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\BadResponseException;

class EbayController extends Controller
{
    public function index()
    {
        return Seller::all()->where('positive_feedback_percent', '>', 99.5);
    }

    public function add()
    {

     /*   $itemId = $item->itemId;                                    //  Идентификатор Продукта
        $title = $item->title;                                      //  Название Продукта
        $globalId = $item->globalId;                                //  Идентификатор Магазина
        $primaryCategory = $item->primaryCategory[0];               //  Информация о Категории
        $categoryId = $primaryCategory->categoryId;                 //  Идентификатор Категории
        $categoryName = $primaryCategory->categoryName;             //  Название Категории
        $galleryURL = $item->galleryURL;                            //  Ссылка на изорбажение
        $viewItemURL = $item->viewItemURL;                          //  Ссылка на продукт
       // $paymentMethod = $item->paymentMethod;                      //  Способ Оплаты
        $autoPay = $item->autoPay;                                  //  Автооплата
        $postalCode = $item->postalCode;                            //  Почтовый индекс
        $lcoation = $item->location;                                //  Локация
        $country = $item->country;                                  //  Страна


        print_r($sellerInfo);
        $sellerUserName = $sellerInfo->sellerUserName;                  //  Имя продавца
        $sellerfeedbackScore = $sellerInfo->feedbackScore;                   //  Количество отзывов продавца
        $sellerpositiveFeedbackPercent = $sellerInfo->positiveFeedbackPercent;         //  Процент отзывов продавца
        $sellerfeedbackRatingStar = $sellerInfo->feedbackRatingStar;              //  Рейтинг отзывов продавца
        $sellertopRatedSeller = $sellerInfo->topRatedSeller;                  //  Присутствие в топе рейтинга продавцов

        $shippingInfo = $item->shippingInfo[0];                                 //  Информация по доставке

        $shippingServiceCost = $shippingInfo->shippingServiceCost[0];           //  Стоимость доставки
        $shippingCurrencyId = $shippingServiceCost->currencyId;                    //  Валюта
        $ShippingValue = $shippingServiceCost->__value__;                           //  Стоимость

        $shippingType = $shippingInfo->shippingType;                                //  Способ доставки
        $shipToLocations = $shippingInfo->shipToLocations;                          //  Международное местоположение или регион, в который продавец желает отправить товар
        $expeditedShipping = $shippingInfo->expeditedShipping;                      //  Доступность ускоренной доставки
        $oneDayShippingAvailable = $shippingInfo->oneDayShippingAvailable;          //  Доступность дсотавки за 1 день
        $handlingTime = $shippingInfo->handlingTime;                                //  Количество дней на отправку товара

        $sellingStatus = $item->sellingStatus[0];                                           //  Статус продажи
        $currentPrice = $sellingStatus->currentPrice[0];                                    //  Текущая стоимость товара
        $currentPriceCurrencyId = $currentPrice->currencyId;                               //  Валюта
        $currentPriceValue = $currentPrice->__value__;                                      //  Стоимость

        $convertedPrice = $sellingStatus->converted[0];                                     //  Конвертированая стоимость товара
        $convertedPriceCurrencyId = $convertedPrice->currencyId;                           //  Валюта
        $convertedPriceValue = $convertedPrice->__value__;                                  //  Стоимость

        $sellingState = $sellingStatus->sellingState;                               //   статус листинга в рабочем процессе обработки eBay
        $timeLeft = $sellingStatus->timeLeft;                                       //   Время, оставшееся до окончания листинга

        $listingInfo = $item->listingInfo[0];                                      //  Информация по листингу
            $bestOfferEnabled = $listingInfo->bestOfferEnabled;                         //   примет ли продавец наилучшее предложение для связанного товара
            $buyItNowAvailable = $listingInfo->buyItNowAvailable;                       //  позволяет пользователю приобретать товар по фиксированной цене
            $startTime = $listingInfo->startTime;                                       //  	Цена покупки товара
            $endTime = $listingInfo->endTime;                                           //  	Цена покупки товара
            $listingType = $listingInfo->listingType;                                   //  Формат листинга
            $gift = $listingInfo->gift;                                                 //  Если значение true, знакчок общего подарка отображает следующий заголовок листинга на страницах поиска и просмотра
            $watchCount = $listingInfo->watchCount;                                     //  Количество просмотров

        $returnsAccepted = $item->returnsAccepted;                                      //  Возврат товара
        $condition = $item->condition[0];                                     //  Состояние товара
            $conditionId = $condition->conditionId;                               // Идентификатор состояния
            $conditionDisplayName = $condition->conditionDisplayName;             //  Отображаемое имя состояния
        $isMultiVariationListing = $item->isMultiVariationListing;            //  Наличие Вариантов Листинга
        $topRatedListing = $item->topRatedListing;                            //  является ли список позиций листингом Top-Rated Plus*/

        $client = new Client();
        $response = $client->get('http://svcs.ebay.com/services/search/FindingService/v1?OPERATION-NAME=findItemsAdvanced&SERVICE-VERSION=1.13.0&SECURITY-APPNAME=DmitriyS-SDKOA-PRD-769dbd521-3986ee4d&RESPONSE-DATA-FORMAT=json&REST-PAYLOAD=true&paginationInput.entriesPerPage=100&paginationInput.pageNumber=1&keywords=toy&itemFilter(0).name=MinPrice&itemFilter(0).value=10.00&itemFilter(0).paramName=Currency&itemFilter(0).paramValue=USD&itemFilter(1).name=MaxPrice&itemFilter(1).value=60.00&itemFilter(1).paramName=Currency&itemFilter(1).paramValue=USD&itemFilter(2).name=FreeShippingOnly&itemFilter(2).value=true&itemFilter(3).name=Condition&itemFilter(3).value=1000&itemFilter(4).name=MinQuantity&itemFilter(4).value=3&itemFilter(5).name=FeedbackScoreMin&itemFilter(5).value=300&itemFilter(6).name=positiveFeedbackPercent&itemFilter(6).value=99.6&itemFilter(7).name=ReturnsAcceptedOnly&itemFilter(7).value=true&outputSelector=SellerInfo');
        /*$url = 'http://svcs.ebay.com/services/search/FindingService/v1';
        $response = $client->get($url, array(), array(
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
                'outputSelector' => 'SellerInfo',
                )
        ));*/
        $result = $response->getBody()->getContents();

        $result = json_decode($result);
        $AR = $result->findItemsAdvancedResponse;

        foreach($AR[0]->searchResult[0]->item as $item) {

           $sellerInfo = $item->sellerInfo[0];  //  Информация о продавце
           $seller = Seller::where('user_name', $sellerInfo->sellerUserName[0])->get();
           if(count($seller)) {
               continue;
           }

           $seller = new Seller();
           $seller->user_name = $sellerInfo->sellerUserName[0];
           $seller->feedback_score = $sellerInfo->feedbackScore[0];
           $seller->positive_feedback_percent = $sellerInfo->positiveFeedbackPercent[0];
           $seller->feedback_rating_star = $sellerInfo->feedbackRatingStar[0];
           $seller->top_rated_seller = $sellerInfo->topRatedSeller[0];
           $seller->save();

           print_r($sellerInfo);
        }
    }
}
