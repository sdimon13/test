<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('item_id')->unique();
            $table->integer('seller_id');
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('brand')->nullable();
            $table->double('price');
            $table->integer('quantity')->nullable();
            $table->string('global_id');
            $table->integer('category_id');
            $table->string('item_url');
            $table->string('location');
            $table->string('country');
            $table->double('shipping_cost');
            $table->string('condition_name');
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
        Schema::dropIfExists('products');
    }
}
