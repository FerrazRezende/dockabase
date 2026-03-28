<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('databases', function (Blueprint $table) {
            $table->char('id', 27)->primary();
            $table->string('name', 64)->unique();
            $table->string('display_name', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('host', 255)->default('localhost');
            $table->unsignedInteger('port')->default(5432);
            $table->string('database_name', 64);
            $table->boolean('is_active')->default(true);
            $table->jsonb('settings')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('databases');
    }
};
