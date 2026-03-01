<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeUsersTable();
        $this->normalizeColocationsTable();
        $this->normalizeMembershipTable();
        $this->normalizeCategoriesTable();
        $this->normalizeExpensesTable();
        $this->normalizePaymentsTable();
    }

    public function down(): void
    {
        // This migration aligns schema drift between previous attempts.
        // Reverting each normalization is intentionally skipped.
    }

    private function normalizeUsersTable(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'reputation')) {
                $table->integer('reputation')->default(0);
            }

            if (! Schema::hasColumn('users', 'is_banned')) {
                $table->boolean('is_banned')->default(false);
            }
        });
    }

    private function normalizeColocationsTable(): void
    {
        if (! Schema::hasTable('colocations')) {
            return;
        }

        Schema::table('colocations', function (Blueprint $table) {
            if (! Schema::hasColumn('colocations', 'status')) {
                $table->string('status')->default('active');
            }
        });

        if (! $this->indexExists('colocations', 'colocations_invite_token_unique')) {
            Schema::table('colocations', function (Blueprint $table) {
                $table->unique('invite_token');
            });
        }
    }

    private function normalizeMembershipTable(): void
    {
        if (! Schema::hasTable('colocation_user')) {
            return;
        }

        Schema::table('colocation_user', function (Blueprint $table) {
            if (! Schema::hasColumn('colocation_user', 'role')) {
                $table->string('role')->default('member');
            }

            if (! Schema::hasColumn('colocation_user', 'left_at')) {
                $table->dateTime('left_at')->nullable();
            }
        });

        DB::table('colocation_user')
            ->whereNotIn('role', ['owner', 'member'])
            ->update(['role' => 'member']);

        // Keep one row per (colocation_id, user_id) before adding the unique constraint.
        $duplicates = DB::table('colocation_user')
            ->select('colocation_id', 'user_id', DB::raw('MAX(id) as keep_id'))
            ->groupBy('colocation_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('colocation_user')
                ->where('colocation_id', $duplicate->colocation_id)
                ->where('user_id', $duplicate->user_id)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        if (! $this->indexExists('colocation_user', 'colocation_user_colocation_id_user_id_unique')) {
            Schema::table('colocation_user', function (Blueprint $table) {
                $table->unique(['colocation_id', 'user_id']);
            });
        }
    }

    private function normalizeCategoriesTable(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        if (! $this->indexExists('categories', 'categories_colocation_id_name_unique')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unique(['colocation_id', 'name']);
            });
        }
    }

    private function normalizeExpensesTable(): void
    {
        if (! Schema::hasTable('expenses')) {
            return;
        }

        if (Schema::hasColumn('expenses', 'date') && ! Schema::hasColumn('expenses', 'expense_date')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->renameColumn('date', 'expense_date');
            });
        }

        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'title')) {
                $table->string('title')->nullable();
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'title')) {
                $table->string('title')->nullable()->change();
            }

            if (Schema::hasColumn('expenses', 'amount')) {
                $table->decimal('amount', 10, 2)->change();
            }

            if (Schema::hasColumn('expenses', 'expense_date')) {
                $table->date('expense_date')->change();
            }
        });
    }

    private function normalizePaymentsTable(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'amount')) {
                $table->decimal('amount', 10, 2)->change();
            }

            if (Schema::hasColumn('payments', 'paid_at')) {
                $table->dateTime('paid_at')->change();
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA index_list('$table')");

            foreach ($rows as $row) {
                if (($row->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'mysql') {
            $rows = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$indexName]);

            return ! empty($rows);
        }

        if ($driver === 'pgsql') {
            return DB::table('pg_indexes')
                ->where('tablename', $table)
                ->where('indexname', $indexName)
                ->exists();
        }

        return false;
    }
};
