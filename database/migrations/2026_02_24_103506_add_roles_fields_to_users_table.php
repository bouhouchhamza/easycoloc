<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('user');
            }
            if (!Schema::hasColumn('users', 'reputation')) {
                $table->integer('reputation')->default(0);
            }
            if(!Schema::hasColumn('users','is_banned')){
            $table->boolean('is_banned')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols=[];
            if(Schema::hasColumn('users','role')) $cols[]='role';
            if(Schema::hasColumn('users','reputation')) $cols [] = 'reputation';
            if(Schema::hasColumn('users','is_banned')) $cols[] = 'is_banned';
                if(!empty($cols)) $table->dropColumn($cols);
        });
    }
};
