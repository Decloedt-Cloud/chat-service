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
            if (Schema::hasColumn('users', 'wap_user_id') && !Schema::hasColumn('users', 'user_id')) {
                $table->renameColumn('wap_user_id', 'user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'user_id') && !Schema::hasColumn('users', 'wap_user_id')) {
                $table->renameColumn('user_id', 'wap_user_id');
            }
        });
    }
};
