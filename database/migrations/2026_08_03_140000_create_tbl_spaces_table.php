<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_spaces', function (Blueprint $table) {
            $table->id('space_id');
            $table->foreignId('user_id')->constrained('tbl_users', 'user_id')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('personal');
            $table->decimal('target_amount', 15, 2)->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_spaces');
    }
};
