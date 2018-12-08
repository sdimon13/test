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
            $table->bigInteger('item_id')->nullable();
            $table->bigInteger('parent_id')->nullable();
            $table->integer('seller_id');
            $table->char('sku')->nullable();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('brand')->nullable();
            $table->double('price')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('quantity_sold')->nullable();
            $table->string('global_id');
            $table->integer('category_id');
            $table->string('item_url');
            $table->string('location');
            $table->string('country');
            $table->double('shipping_cost');
            $table->string('condition_name');
            $table->string('main_photo')->nullable();
            $table->boolean('variation')->nullable();
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
