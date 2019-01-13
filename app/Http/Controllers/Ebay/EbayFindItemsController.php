<?php

namespace App\Http\Controllers\Ebay;


use App\Classes\EbayFindItemsAdvanced;
use App\Http\Controllers\Controller;
use App\Models\Ebay\Keyword;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class EbayFindItemsController extends Controller
{
    public function index(Request $request)
    {
        $keywords = Keyword::whereHas('users', function ($query) use ($request) {
            $query->where('user_id', \Auth::user()->id);
        });

        if (!is_null($request->keywords)) {
            $keywords->where('name', $request->keywords);
        }

        if (!is_null($request->min_price)) {
            $keywords->where('min_price', '>=', $request->min_price);
        }

        if (!is_null($request->max_price)) {
            $keywords->where('max_price', '<=', $request->max_price);
        }

        if (!is_null($request->feedback_score_min)) {
            $keywords->where('feedback_score_min', '>=', $request->feedback_score_min);
        }

        if (!is_null($request->feedback_score_max)) {
            $keywords->where('feedback_score_max', '<=', $request->feedback_score_max);
        }

        return view('ebay/findItems', [
            'keywords' => $keywords->paginate(10)->appends($_GET)
        ]);
    }

    public function findItemsAdvanced()
    {

        $userId = \Auth::user()->id;
        $params = [
            'userId' => $userId,
            'keywords' => 'bath mats',
            'pageNumber' => 1,
            'minPrice' => 10.00,
            'maxPrice' => 20.00,
            'feedbackScoreMin' => 300,
            'feedbackScoreMax' => 999999999,
            ];
        $keyword = Keyword::firstOrCreate([
           'name' =>  'bath mats',
           'min_price' =>  10.00,
           'max_price' =>  20.00,
           'feedback_score_min' =>  300,
           'feedback_score_max' =>  999999999,
        ]);

        $keyword->users()->syncWithoutDetaching([$userId]);
        dispatch(new \App\Jobs\Ebay\EbayFindItemsAdvanced($params))->onQueue('findItems');;
    }

    public function checkCount(Request $request)
    {
        $request->validate([
            'keywords' => ['required', 'string']
        ]);

        $keywords = $request->keywords;
        $pageNumber = 1;

        if (!is_null($request->min_price)) {
            $minPrice = $request->min_price;
        } else {
            $minPrice = 10.00;
        }

        if (!is_null($request->max_price)) {
            $maxPrice = $request->max_price;
        } else {
            $maxPrice = 70.00;
        }

        if (!is_null($request->feedback_score_min)) {
            $feedbackScoreMin = $request->feedback_score_min;
        } else {
            $feedbackScoreMin = 300;
        }

        if (!is_null($request->feedback_score_max)) {
            $feedbackScoreMax = $request->feedback_score_max;
        } else {
            $feedbackScoreMax = 999999999;
        }

        //check which submit was clicked on
        if($request->submit == 'check') {
            $client = new Client();
            $url = 'http://svcs.ebay.com/services/search/FindingService/v1';
            $response = $client->get($url, array(
                'query' => array(
                    'OPERATION-NAME' => 'findItemsAdvanced',
                    'SERVICE-VERSION' => '1.13.0',
                    'SECURITY-APPNAME' => env('SECURITY_APPNAME'),
                    'RESPONSE-DATA-FORMAT' => 'json',
                    'REST-PAYLOAD' => 'true',
                    'paginationInput.entriesPerPage' => '100',
                    'paginationInput.pageNumber' => $pageNumber,
                    'keywords' => $keywords,
                    'itemFilter(0).name' => 'LocatedIn',
                    'itemFilter(0).value' => 'US',
                    'itemFilter(1).name' => 'MinPrice',
                    'itemFilter(1).value' => $minPrice,
                    'itemFilter(1).paramName' => 'Currency',
                    'itemFilter(1).paramValue' => 'USD',
                    'itemFilter(2).name' => 'MaxPrice',
                    'itemFilter(2).value' => $maxPrice,
                    'itemFilter(2).paramName' => 'Currency',
                    'itemFilter(2).paramValue' => 'USD',
                    'itemFilter(3).name' => 'FreeShippingOnly',
                    'itemFilter(3).value' => 'true',
                    'itemFilter(4).name' => 'Condition',
                    'itemFilter(4).value' => '1000',
                    'itemFilter(5).name' => 'MinQuantity',
                    'itemFilter(5).value' => '3',
                    'itemFilter(6).name' => 'FeedbackScoreMin',
                    'itemFilter(6).value' => $feedbackScoreMin,
                    'itemFilter(7).name' => 'FeedbackScoreMax',
                    'itemFilter(7).value' => $feedbackScoreMax,
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

            return view('ebay/findItems', [
                'keywords' => Keyword::whereHas('users', function ($query) use ($request) {
                    $query->where('user_id', \Auth::user()->id);
                })->paginate(10),
                'totalEntries' => $totalEntries,
                'totalPages' => $totalPages]);


        } elseif($request->submit == 'send') {
            $userId = \Auth::user()->id;
            $params = [
                'userId' => $userId,
                'keywords' => $keywords,
                'pageNumber' => $pageNumber,
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
                'feedbackScoreMin' => $feedbackScoreMin,
                'feedbackScoreMax' => $feedbackScoreMax,
            ];
            dispatch(new \App\Jobs\Ebay\EbayFindItemsAdvanced($params))->onQueue('findItems');;

            return view('ebay/findItems', [
                'keywords' => Keyword::whereHas('users', function ($query) use ($request) {
                    $query->where('user_id', \Auth::user()->id);
                })->paginate(10),
                ]);
        }
    }
}
