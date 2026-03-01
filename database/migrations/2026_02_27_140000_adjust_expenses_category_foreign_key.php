<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expenses') || ! Schema::hasColumn('expenses', 'category_id')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            if ($this->hasForeignKey('expenses', 'expenses_category_id_foreign')) {
                $table->dropForeign('expenses_category_id_foreign');
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->change();
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('expenses') || ! Schema::hasColumn('expenses', 'category_id')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            if ($this->hasForeignKey('expenses', 'expenses_category_id_foreign')) {
                $table->dropForeign('expenses_category_id_foreign');
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->cascadeOnDelete();
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

