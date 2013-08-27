<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAssetsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('assets', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('role_id')->unsigned()->index();
			$table->integer('asset_type_id')->unsigned()->index();
			$table->smallInteger('ownership')->default(1);
			$table->string('name')->nullable();
			$table->string('maker')->nullable();
			$table->string('model')->nullable();
			$table->string('product')->nullable();
			$table->string('serial')->nullable();
			$table->text('license')->nullable();
			$table->smallInteger('purchase_year');
			$table->smallInteger('lifespan');
			$table->decimal('purchase_cost', 10, 2);
			$table->decimal('replace_cost', 10, 2);
			$table->boolean('recurring')->default(0);
			$table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('assets');
	}

}
