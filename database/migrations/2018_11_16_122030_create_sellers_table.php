<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSellersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sellers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_name')->unique();
            $table->integer('feedback_score')->index();
            $table->double('positive_feedback_percent')->index();
            $table->string('feedback_rating_star');
            $table->string('top_rated_seller');
            $table->string('country')->nullable()->index();
            $table->timestamp('date_reg')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sellers');
    }
}
