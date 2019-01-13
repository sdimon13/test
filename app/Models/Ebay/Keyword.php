<?php

namespace App\Models\Ebay;

use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    protected $fillable = [
        'name',
        'total_products',
        'total_pages',
        'parsed_pages',
        'min_price',
        'max_price',
        'feedback_score_min',
        'feedback_score_max'
    ];

    public function users()
    {
        return $this->belongsToMany('App\User');
    }

    public function products()
    {
        return $this->belongsToMany('App\Models\Ebay\Product');
    }
}
