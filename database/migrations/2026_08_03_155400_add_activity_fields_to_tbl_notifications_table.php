<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_notifications', function (Blueprint $table) {
            $table->foreignId('space_id')->nullable()->after('user_id')->constrained('tbl_spaces', 'space_id')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->after('space_id')->constrained('tbl_users', 'user_id')->nullOnDelete();
            $table->string('action')->nullable()->after('type');
            $table->decimal('amount', 15, 2)->nullable()->after('action');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('space_id');
            $table->dropConstrainedForeignId('actor_user_id');
            $table->dropColumn(['action', 'amount']);
        });
    }
};
