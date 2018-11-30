<?php

namespace App\Models\Ebay;

use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
   protected $fillable = ['user_name, feedback_score, positive_feedback_percent, feedback_rating_star, top_rated_seller'];

    public function products()
    {
        return $this->hasMany('App\Models\Ebay\Product');
    }
}
