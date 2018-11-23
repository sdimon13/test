<?php

namespace App\Jobs;

use App\Seller;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class GetCustomerInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    protected $user_name;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($name)
    {
        $this->user_name = $name;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info( $this->user_name);
        $html = file_get_contents('https://www.ebay.com/usr/'.$this->user_name);
        $crawler = new Crawler(null, 'https://www.ebay.com/usr/'.$this->user_name);
        $crawler->addHtmlContent($html, 'UTF-8');
        $date_reg = $crawler->filter('#member_info .info')->text();
        $country = $crawler->filter('#member_info .mem_loc')->text();

        $seller = Seller::where('user_name', $this->user_name)
            ->update([
                'country' => $country,
                'date_reg' => \Carbon\Carbon::parse($date_reg),
            ]);
    }
}
