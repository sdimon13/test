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
            $table->increments('id')->index();
            $table->bigInteger('item_id')->unique()->nullable();
            $table->bigInteger('parent_id')->nullable()->index();
            $table->integer('seller_id')->index();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('brand')->nullable()->index();
            $table->double('price')->nullable()->index();
            $table->integer('quantity')->nullable()->index();
            $table->integer('quantity_sold')->nullable()->index();
            $table->string('global_id');
            $table->integer('category_id');
            $table->string('item_url');
            $table->string('location');
            $table->string('country')->index();
            $table->double('handling_time')->index();
            $table->string('condition_name');
            $table->boolean('variation')->nullable()->index();
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
