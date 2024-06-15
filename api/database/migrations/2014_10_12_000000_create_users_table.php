<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ownername', 255);
            $table->string('email', 150);
            $table->string('password', 60);
            $table->string('avatar_url', 50);
			$table->text('shopurl');
            $table->string('token', 255);
            $table->enum('status', array('active', 'inactive'));
            $table->tinyInteger('catalogfetched');
			$table->tinyInteger('shopifyimported');
            $table->tinyInteger('fbainvnt');
            $table->string('tempcode', 30);
            $table->tinyInteger('installationstatus');
			$table->enum('membershiptype', array('free', 'plan'));
            $table->integer('plan');
            $table->tinyInteger('sync');
            $table->dateTime('storecreated_at', 50);
			$table->dateTime('storeupdated_at');
            $table->string('plan_name', 255);
            $table->integer('skulimit');
			$table->integer('skuconsumed');
            $table->tinyInteger('tosaccepted');
            $table->tinyInteger('includeoutofstock');
            $table->tinyInteger('publishstatus');
			$table->tinyInteger('keysemail');
            $table->rememberToken();
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
        Schema::drop('users');
    }
}
