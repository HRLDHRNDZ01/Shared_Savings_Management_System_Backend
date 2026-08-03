<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_space_members', function (Blueprint $table) {
            $table->id('space_member_id');
            $table->foreignId('space_id')->constrained('tbl_spaces', 'space_id')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('tbl_users', 'user_id')->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->timestamps();

            $table->unique(['space_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_space_members');
    }
};
