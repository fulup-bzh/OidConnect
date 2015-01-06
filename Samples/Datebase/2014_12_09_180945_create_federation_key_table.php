<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFederationKeyTable extends Migration {

    protected $primaryKey = 'id';
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('federation_keys', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('social_uid');
            $table->integer('idp_uid')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->tinyInteger('social_loa');
            $table->timestamps();

            $table->unique(['idp_uid', 'social_uid']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()	{
        Schema::drop('federation_keys');
	}
}
