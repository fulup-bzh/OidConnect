<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCheckCodeTable extends Migration {

   protected $primaryKey = 'id';
	/**
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('check_codes', function(Blueprint $table)
        {
            $table->increments('id');
            $table->char('code',255)->unique();
            $table->string('email')->unique();
            $table->integer('user_id')->unsigned();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()	{
        Schema::drop('check_codes');
	}
}
