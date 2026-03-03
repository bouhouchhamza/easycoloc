<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles')) {
            return;
        }

        $this->ensureRole(User::ROLE_GLOBAL_ADMIN);
        $this->ensureRole(User::ROLE_USER);

        $this->migrateLegacyAdminAssignments();

        if (Schema::hasColumn('users', 'role')) {
            $this->backfillFromUsersRoleColumn();
        }

        $this->ensureEveryUserHasRole();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default(User::ROLE_USER);
            });
        }

        DB::table('users')->update(['role' => User::ROLE_USER]);

        $globalAdminUserIds = $this->userIdsWithGlobalAdminRole();

        if (! empty($globalAdminUserIds)) {
            DB::table('users')
                ->whereIn('id', $globalAdminUserIds)
                ->update(['role' => User::ROLE_GLOBAL_ADMIN]);
        }
    }

    private function ensureRole(string $name): void
    {
        Role::findOrCreate($name, 'web');
    }

    private function migrateLegacyAdminAssignments(): void
    {
        $globalAdminRoleId = $this->roleId(User::ROLE_GLOBAL_ADMIN);
        $legacyRoleId = $this->roleId('admin');

        if (! $globalAdminRoleId || ! $legacyRoleId) {
            return;
        }

        $legacyUserIds = DB::table('model_has_roles')
            ->where('role_id', $legacyRoleId)
            ->where('model_type', User::class)
            ->pluck('model_id');

        foreach ($legacyUserIds as $userId) {
            $this->attachRoleIfMissing((int) $userId, $globalAdminRoleId);
        }

        DB::table('model_has_roles')
            ->where('role_id', $legacyRoleId)
            ->where('model_type', User::class)
            ->delete();
    }

    private function backfillFromUsersRoleColumn(): void
    {
        $globalAdminRoleId = $this->roleId(User::ROLE_GLOBAL_ADMIN);
        $userRoleId = $this->roleId(User::ROLE_USER);

        if (! $globalAdminRoleId || ! $userRoleId) {
            return;
        }

        $users = DB::table('users')->select('id', 'role')->get();

        foreach ($users as $user) {
            $legacyRole = strtolower((string) ($user->role ?? ''));
            $targetRoleId = in_array($legacyRole, ['admin', 'global_admin'], true)
                ? $globalAdminRoleId
                : $userRoleId;

            $this->attachRoleIfMissing((int) $user->id, $targetRoleId);
        }
    }

    private function ensureEveryUserHasRole(): void
    {
        $userRoleId = $this->roleId(User::ROLE_USER);

        if (! $userRoleId) {
            return;
        }

        $usersWithAnyRole = DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->distinct()
            ->pluck('model_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $query = DB::table('users')->select('id');

        if (! empty($usersWithAnyRole)) {
            $query->whereNotIn('id', $usersWithAnyRole);
        }

        foreach ($query->pluck('id') as $userId) {
            $this->attachRoleIfMissing((int) $userId, $userRoleId);
        }
    }

    private function roleId(string $name): ?int
    {
        $id = DB::table('roles')
            ->where('name', $name)
            ->where('guard_name', 'web')
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function attachRoleIfMissing(int $userId, int $roleId): void
    {
        $exists = DB::table('model_has_roles')
            ->where('role_id', $roleId)
            ->where('model_type', User::class)
            ->where('model_id', $userId)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $userId,
        ]);
    }

    private function userIdsWithGlobalAdminRole(): array
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', User::class)
            ->whereIn('roles.name', [User::ROLE_GLOBAL_ADMIN, 'admin'])
            ->pluck('model_has_roles.model_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }
};
