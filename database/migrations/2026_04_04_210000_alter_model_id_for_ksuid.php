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
        // Change model_id to string to support KSUID
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->string('model_id')->change();
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->string('model_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to bigint
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->bigInteger('model_id')->change();
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->bigInteger('model_id')->change();
        });
    }
};
