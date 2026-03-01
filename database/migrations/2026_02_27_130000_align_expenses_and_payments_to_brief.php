<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expenses')) {
            if (Schema::hasColumn('expenses', 'date') && ! Schema::hasColumn('expenses', 'expense_date')) {
                Schema::table('expenses', function (Blueprint $table) {
                    $table->renameColumn('date', 'expense_date');
                });
            }

            Schema::table('expenses', function (Blueprint $table) {
                if (Schema::hasColumn('expenses', 'amount')) {
                    $table->decimal('amount', 10, 2)->change();
                }

                if (Schema::hasColumn('expenses', 'category_id')) {
                    $table->unsignedBigInteger('category_id')->nullable()->change();
                }
            });
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'amount')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->decimal('amount', 10, 2)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                if (Schema::hasColumn('expenses', 'expense_date') && ! Schema::hasColumn('expenses', 'date')) {
                    $table->renameColumn('expense_date', 'date');
                }
            });

            Schema::table('expenses', function (Blueprint $table) {
                if (Schema::hasColumn('expenses', 'amount')) {
                    $table->decimal('amount', 12, 2)->change();
                }

                if (Schema::hasColumn('expenses', 'category_id')) {
                    $table->unsignedBigInteger('category_id')->nullable(false)->change();
                }
            });
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'amount')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->decimal('amount', 12, 2)->change();
            });
        }
    }
};
