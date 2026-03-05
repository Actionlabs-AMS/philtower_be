<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		// Disable foreign key checks temporarily
		DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

		Schema::create('dashboard_widgets', function (Blueprint $table) {
			$table->engine = 'InnoDB';
			$table->bigIncrements('id');
			$table->bigInteger('user_id')->unsigned()->nullable()->index();
			$table->string('widget_type', 100)->index();
			$table->string('title', 255);
			$table->string('data_source', 255)->nullable();
			$table->json('query_config')->nullable();
			$table->json('visualization_config')->nullable();
			$table->integer('position_x')->default(0);
			$table->integer('position_y')->default(0);
			$table->integer('width')->default(4);
			$table->integer('height')->default(3);
			$table->integer('order_index')->default(0);
			$table->boolean('active')->default(true);
			$table->timestamps();

			// Foreign key constraint
			$table->foreign('user_id')
				->references('id')
				->on('users')
				->onDelete('cascade');

			// Indexes
			$table->index(['user_id', 'active']);
			$table->index(['user_id', 'order_index']);
		});

		// Re-enable foreign key checks
		DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		// Disable foreign key checks temporarily
		DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

		Schema::dropIfExists('dashboard_widgets');

		// Re-enable foreign key checks
		DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
	}
};

