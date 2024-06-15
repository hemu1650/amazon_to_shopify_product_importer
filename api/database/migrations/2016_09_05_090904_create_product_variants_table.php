<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_id');
			$table->string('sku', 100);
			$table->string('option1val', 100);
			$table->string('option2val', 100);
			$table->string('option3val', 100);
			$table->float('price');
			$table->integer('quantity');
			$table->tinyInteger('newflag');
			$table->tinyInteger('quantityflag');
			$table->tinyInteger('priceflag');
			$table->tinyInteger('block');
			$table->tinyInteger('duplicate');
			$table->enum('status', array('Already Exist', 'Ready to Import', 'Import in progress', 'Imported'));
			$table->string('shopifyproductid', 30);
			$table->string('shopifyvariantid', 30);
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
        Schema::drop('product_variants');
    }
}
