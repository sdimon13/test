<?php

namespace App\Models\Ebay;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['item_id', 'seller_id', 'title', 'global_id', 'category_id', 'item_url', 'location',
        'country', 'handling_time', 'condition_name', 'variation'];

    public function photos()
    {
        return $this->hasMany('App\Models\Ebay\Photo');
    }

    public function shippings()
    {
        return $this->hasMany('App\Models\Ebay\Shipping');
    }

    public function keywords()
    {
        return $this->belongsToMany('App\Models\Ebay\Keyword');
    }
}
