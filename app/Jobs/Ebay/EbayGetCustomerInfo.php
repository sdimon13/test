<?php

namespace App\Jobs\Ebay;

use App\Seller;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class EbayGetCustomerInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    protected $userName;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($name)
    {
        $this->userName = $name;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info( $this->userName);
        $html = file_get_contents('https://www.ebay.com/usr/'.$this->userName);
        $crawler = new Crawler(null, 'https://www.ebay.com/usr/'.$this->userName);
        $crawler->addHtmlContent($html, 'UTF-8');
        $date_reg = $crawler->filter('#member_info .info')->text();
        $country = $crawler->filter('#member_info .mem_loc')->text();

        $seller = Seller::where('user_name', $this->userName)
            ->update([
                'country' => $country,
                'date_reg' => \Carbon\Carbon::parse($date_reg),
            ]);
    }
}
