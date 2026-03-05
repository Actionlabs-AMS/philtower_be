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

        // Create users table
        Schema::create('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('user_login')->unique();
            $table->string('user_email')->unique();
            $table->string('user_pass');
            $table->string('user_salt');
            $table->tinyInteger('user_status')->default(0);
            $table->string('user_activation_key')->nullable();
            $table->bigInteger('role_id')->unsigned()->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create password reset tokens table
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Create failed jobs table
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // Note: personal_access_tokens table is created by Laravel Sanctum

        // Create roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->boolean('is_super_admin')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // Add foreign key constraint for users.role_id after roles table is created
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
        });

        // Create permissions table
        Schema::create('permissions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('label');
            $table->timestamps();
        });

        // Create navigations table
        Schema::create('navigations', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug');
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('parent_id')->nullable()->unsigned();
            $table->boolean('active')->default(true);
            $table->boolean('show_in_menu')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Self-referencing foreign key
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('navigations')
                  ->onDelete('cascade');
        });

        // Create role_permissions table
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->bigInteger('role_id')->unsigned();
            $table->bigInteger('navigation_id')->unsigned();
            $table->bigInteger('permission_id')->unsigned();
            $table->boolean('allowed')->default(false);
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('navigation_id')->references('id')->on('navigations')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });

        // Create two_factor_auths table
        Schema::create('two_factor_auths', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('email_code', 6)->nullable();
            $table->timestamp('email_code_expires_at')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->json('backup_codes')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_enabled']);
        });

        // Create user_meta table
        Schema::create('user_meta', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->unsigned();
            $table->string('meta_key');
            $table->longText('meta_value')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'meta_key']);
        });

        // Create categories table
        Schema::create('categories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('descriptions')->nullable();
            $table->bigInteger('parent_id')->nullable()->unsigned();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
        });

        // Create tags table
        Schema::create('tags', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('descriptions')->nullable();
            $table->string('color', 7)->default('#007bff');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Create pages table
        Schema::create('pages', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable(); // Legacy content field (kept for backward compatibility)
            $table->json('layout_structure')->nullable(); // Primary content storage for page builder (supports nested blocks including columns)
            $table->string('layout')->default('default'); // Layout template (kept for backward compatibility, always 'default' for page builder)
            $table->bigInteger('author_id')->nullable()->unsigned();
            $table->string('featured_image')->nullable(); // Legacy field (images now added via page builder blocks)
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('status')->default('draft'); // draft, published, scheduled
            $table->timestamp('published_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('author_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['status', 'active']);
            $table->index('published_at');
        });

        // Create media_libraries table
        Schema::create('media_libraries', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->nullable()->unsigned()->index();
            $table->string('file_name')->index();
            $table->string('file_type')->index();
            $table->string('file_size')->nullable();
            $table->integer('width')->unsigned()->default(0);
            $table->integer('height')->unsigned()->default(0);
            $table->string('file_dimensions')->nullable();
            $table->mediumText('file_url')->nullable();
            $table->mediumText('thumbnail_url')->nullable();
            $table->string('caption')->nullable();
            $table->string('short_descriptions')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // Create options table
        Schema::create('options', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('option_key')->unique();
            $table->longText('option_value')->nullable();
            $table->string('option_type')->default('string');
            $table->text('description')->nullable();
            $table->timestamps();
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

        // Drop tables in reverse order to avoid foreign key constraints
        Schema::dropIfExists('options');
        Schema::dropIfExists('media_libraries');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('user_meta');
        Schema::dropIfExists('two_factor_auths');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('navigations');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        // Note: personal_access_tokens is handled by Laravel Sanctum
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
