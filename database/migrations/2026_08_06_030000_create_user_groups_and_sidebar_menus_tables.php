<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_user_groups', function (Blueprint $table) {
            $table->id('user_group_id');
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tbl_sidebar_menus', function (Blueprint $table) {
            $table->id('sidebar_menu_id');
            $table->string('key')->unique();
            $table->string('label');
            $table->string('icon')->nullable();
            $table->string('route_name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tbl_group_sidebar_menus', function (Blueprint $table) {
            $table->id('group_sidebar_menu_id');
            $table->foreignId('user_group_id')
                ->constrained('tbl_user_groups', 'user_group_id')
                ->cascadeOnDelete();
            $table->foreignId('sidebar_menu_id')
                ->constrained('tbl_sidebar_menus', 'sidebar_menu_id')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_group_id', 'sidebar_menu_id'], 'group_menu_unique');
        });

        Schema::table('tbl_users', function (Blueprint $table) {
            $table->foreignId('user_group_id')
                ->nullable()
                ->after('role')
                ->constrained('tbl_user_groups', 'user_group_id')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_group_id');
        });

        Schema::dropIfExists('tbl_group_sidebar_menus');
        Schema::dropIfExists('tbl_sidebar_menus');
        Schema::dropIfExists('tbl_user_groups');
    }
};
