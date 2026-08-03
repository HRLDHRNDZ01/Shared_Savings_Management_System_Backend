<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->foreignId('user_id')->constrained('tbl_users', 'user_id')->cascadeOnDelete();
            $table->foreignId('space_id')->constrained('tbl_spaces', 'space_id')->cascadeOnDelete();
            $table->string('type');
            $table->decimal('amount', 15, 2);
            $table->string('note')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_transactions');
    }
};
