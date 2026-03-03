<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'role')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default(User::ROLE_USER);
        });

        DB::table('users')->update(['role' => User::ROLE_USER]);

        $adminUserIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', User::class)
            ->whereIn('roles.name', [User::ROLE_GLOBAL_ADMIN, 'admin'])
            ->pluck('model_has_roles.model_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if (! empty($adminUserIds)) {
            DB::table('users')
                ->whereIn('id', $adminUserIds)
                ->update(['role' => User::ROLE_GLOBAL_ADMIN]);
        }
    }
};
