<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_migrations', function (Blueprint $table) {
            $table->char('id', 27)->primary();
            $table->char('database_id', 27);
            $table->integer('batch');
            $table->string('name');
            $table->string('operation', 50);
            $table->string('table_name', 63);
            $table->string('schema_name', 63)->default('public');
            $table->text('sql_up');
            $table->text('sql_down');
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->foreign('database_id')->references('id')->on('databases')->onDelete('cascade');
            $table->unique(['database_id', 'name']);
        });

        Schema::table('system_migrations', function (Blueprint $table) {
            $table->index('database_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_migrations');
    }
};
