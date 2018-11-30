<?php

namespace App\Models\Ebay;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public function photos()
    {
        return $this->hasMany('App\Models\Ebay\Photo');
    }
}
