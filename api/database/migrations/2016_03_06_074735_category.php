<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class Category extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',50)->nullable();
            $table->string('description',255)->nullable();
            $table->timestamps();
        });
        DB::table('categories')->insert([
            array('name' => 'bug_fix','description' =>str_random(50)),
            array('name' => 'test','description' =>str_random(50)),
            array('name' => 'UI_design','description' =>str_random(50)),
            array('name' => 'Implement','description' =>str_random(50)),
            array('name' => 'documentation','description' =>str_random(50)),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('categories');
    }
}
