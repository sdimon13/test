<?php

namespace App\Jobs\Ebay;

use App\Models\Ebay\Photo;
use App\Models\Ebay\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\DomCrawler\Crawler;

class EbayGetProductInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    protected $id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     * Получаем фото и бренд продукта
     *
     * @return void
     */
    public function handle()
    {
        info('[Ebay-GetProductInfo] Product id: '.$this->id);
        $product = Product::find($this->id);
        $link = $product->item_url;
        $link = str_replace('ebay.com', 'ebay.co.uk', $link);
        $html = file_get_contents($link);

        $crawler = new Crawler(null, $link);
        $crawler->addHtmlContent($html, 'UTF-8');

        // Получаем бренд товара
        $attributes = $crawler->filter('div.itemAttr')->text();
        if (preg_match("|Brand:\s*([^\s]+)|i", $attributes, $matches)) {
            $brand = $matches[1];
            $product = Product::where('id', $this->id)->first();
            $product->brand = $brand;
            $product->save();
            $product->refresh();

           // info('[Ebay-GetProductInfo] ProductId: '.$this->id.' Link: '.$link.' Brand: '.$brand);
        }
        $photos = [];
        // Получаем список изображений товара
        $photos = $crawler->filter('#vi_main_img_fs > ul > li img')->each(function (Crawler $node, $i) {
            return str_replace('s-l64','s-l1000', $node->attr('src'));
        });

        if (!count($photos)) {
            $mainPhoto = 'http://galleryplus.ebayimg.com/ws/web/'.$product->item_id.'_1_1_1.jpg';
            array_unshift($photos, $mainPhoto);
        }

        foreach ($photos as $photoLink) {
            $photo = Photo::firstOrCreate(
                ['product_id' => $product->id],
                ['photo' => $photoLink]
            );
        }
    }
}
