<?php

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
            $table->increments('product_id');
			$table->string('title',500);
			$table->text('description');
			$table->string('feature1', 255);
			$table->string('feature2', 255);
			$table->string('feature3', 255);
			$table->string('feature4', 255);
			$table->string('feature5', 255);
			$table->string('item_note', 500);
			$table->string('brand', 100);
			$table->string('product_type', 100);
			$table->string('option1name', 100);
			$table->string('option2name', 100);
			$table->string('option3name', 100);
			$table->string('shopifyproductid', 30);
			$table->tinyInteger('newflag');
			$table->tinyInteger('quantityflag');
			$table->tinyInteger('priceflag');
			$table->tinyInteger('block');
			$table->tinyInteger('duplicate');
			$table->enum('status', array('Already Exist', 'Ready to Import', 'Import in progress', 'Imported'));
			$table->integer('user_id');
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
        Schema::drop('products');
    }
}
