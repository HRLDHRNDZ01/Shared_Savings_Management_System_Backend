<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_space_invitations', function (Blueprint $table) {
            $table->id('space_invitation_id');
            $table->foreignId('space_id')->constrained('tbl_spaces', 'space_id')->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('tbl_users', 'user_id')->cascadeOnDelete();
            $table->foreignId('invited_user_id')->constrained('tbl_users', 'user_id')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['invited_user_id', 'status']);
            $table->index(['space_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_space_invitations');
    }
};
