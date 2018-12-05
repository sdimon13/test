<?php

namespace App\Jobs\Ebay;

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
     *
     * @return void
     */
    public function handle()
    {
        $product = Product::select('item_url')->find($this->id);
        $link = $product->item_url;
        $link = str_replace('ebay.com', 'ebay.co.uk', $link);
        info('ProductId: '.$this->id.' Link: '.$link);
        $html = file_get_contents($link);

        $crawler = new Crawler(null, $link);
        $crawler->addHtmlContent($html, 'UTF-8');

        $attributes = $crawler->filter('div.itemAttr')->text();
        if (preg_match("|Brand:\s*([^\s]+)|i", $attributes, $matches)) {
            $brand = $matches[1];
            info('Brand: '.$brand);
            $seller = Product::where('id', $this->id)
                ->update([
                    'brand' => $brand,
                ]);
        }
    }
}
