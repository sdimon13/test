<?php

namespace App\Models\Ebay;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
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
