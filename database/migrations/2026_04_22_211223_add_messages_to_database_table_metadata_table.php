<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('database_table_metadata')) {
            return;
        }

        Schema::table('database_table_metadata', function (Blueprint $table) {
            $table->json('messages')->nullable()->after('validations');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('database_table_metadata')) {
            return;
        }

        Schema::table('database_table_metadata', function (Blueprint $table) {
            $table->dropColumn('messages');
        });
    }
};
