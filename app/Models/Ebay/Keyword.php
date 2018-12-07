<?php

namespace App\Models\Ebay;

use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    protected $fillable = ['name', 'total_products'];

    public function users()
    {
        return $this->belongsToMany('App\User');
    }

    public function products()
    {
        return $this->belongsToMany('App\Models\Ebay\Product');
    }
}
