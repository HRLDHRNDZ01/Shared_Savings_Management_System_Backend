<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->string('username')->default('');
            $table->string('first_name')->default('');
            $table->string('last_name')->default('');
        });

        $usedUsernames = [];

        foreach (DB::table('tbl_users')->orderBy('user_id')->get() as $row) {
            $parts = preg_split('/\s+/', trim((string) $row->name), 2, PREG_SPLIT_NO_EMPTY) ?: [];
            $firstName = $parts[0] ?? 'User';
            $lastName = $parts[1] ?? '';

            $base = Str::lower(Str::before((string) $row->email, '@'));
            $base = preg_replace('/[^a-z0-9_]+/', '', $base) ?: 'user'.$row->user_id;
            if (strlen($base) < 3) {
                $base = str_pad($base, 3, '0');
            }

            $username = $base;
            $suffix = 1;

            while (isset($usedUsernames[$username])) {
                $username = $base.$suffix;
                $suffix++;
            }

            $usedUsernames[$username] = true;

            DB::table('tbl_users')->where('user_id', $row->user_id)->update([
                'username' => $username,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => trim($firstName.' '.$lastName),
            ]);
        }

        Schema::table('tbl_users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'first_name', 'last_name']);
        });
    }
};
