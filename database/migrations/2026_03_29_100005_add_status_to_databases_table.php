<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('is_active');
            $table->string('current_step', 30)->nullable()->after('status');
            $table->unsignedTinyInteger('progress')->default(0)->after('current_step');
            $table->text('error_message')->nullable()->after('progress');

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->dropColumn(['status', 'current_step', 'progress', 'error_message']);
        });
    }
};
