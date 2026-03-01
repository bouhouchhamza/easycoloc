<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expenses')) {
            return;
        }

        $addedDateColumn = ! Schema::hasColumn('expenses', 'date');
        $addedCategoryColumn = ! Schema::hasColumn('expenses', 'category_id');

        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'date')) {
                $table->date('date')->nullable()->after('amount');
            }

            if (! Schema::hasColumn('expenses', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('date');
            }
        });

        $colocationIds = DB::table('colocations')->pluck('id');

        foreach ($colocationIds as $colocationId) {
            $categoryId = DB::table('categories')
                ->where('colocation_id', $colocationId)
                ->value('id');

            if (! $categoryId) {
                $categoryId = DB::table('categories')->insertGetId([
                    'name' => 'Other',
                    'colocation_id' => $colocationId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('expenses')
                ->where('colocation_id', $colocationId)
                ->whereNull('category_id')
                ->update(['category_id' => $categoryId]);
        }

        DB::table('expenses')
            ->whereNull('date')
            ->orderBy('id')
            ->chunkById(200, function ($expenses) {
                foreach ($expenses as $expense) {
                    DB::table('expenses')
                        ->where('id', $expense->id)
                        ->update([
                            'date' => $expense->created_at
                                ? Carbon::parse($expense->created_at)->toDateString()
                                : now()->toDateString(),
                        ]);
                }
            });

        if ($addedDateColumn || $addedCategoryColumn) {
            Schema::table('expenses', function (Blueprint $table) {
                if (Schema::hasColumn('expenses', 'date')) {
                    $table->date('date')->nullable(false)->change();
                }

                if (Schema::hasColumn('expenses', 'category_id')) {
                    $table->unsignedBigInteger('category_id')->nullable()->change();
                }
            });
        }

        if (DB::getDriverName() === 'mysql') {
            Schema::table('expenses', function (Blueprint $table) {
                if (! $this->hasForeignKey('expenses', 'expenses_user_id_foreign')) {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                }

                if (! $this->hasForeignKey('expenses', 'expenses_category_id_foreign')) {
                    $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('expenses')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            if ($this->hasForeignKey('expenses', 'expenses_category_id_foreign')) {
                $table->dropForeign('expenses_category_id_foreign');
            }

            if ($this->hasForeignKey('expenses', 'expenses_user_id_foreign')) {
                $table->dropForeign('expenses_user_id_foreign');
            }
        });
    }

    private function hasForeignKey(string $table, string $constraint): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
