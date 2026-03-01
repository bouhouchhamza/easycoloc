<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('role')
                    ->orWhere('role', '')
                    ->orWhere('role', 'member')
                    ->orWhere('role', 'owner');
            })
            ->update(['role' => 'user']);

        DB::table('users')
            ->where('role', 'admin')
            ->update(['role' => 'global_admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        DB::table('users')
            ->where('role', 'global_admin')
            ->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->change();
        });
    }
};
