<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_table_metadata', function (Blueprint $table) {
            $table->char('id', 27)->primary();
            $table->char('database_id', 27);
            $table->string('schema_name', 63)->default('public');
            $table->string('table_name', 63);
            $table->json('columns');
            $table->json('validations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('database_id')->references('id')->on('databases')->onDelete('cascade');
            $table->unique(['database_id', 'schema_name', 'table_name']);
        });

        Schema::table('database_table_metadata', function (Blueprint $table) {
            $table->index('database_id');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_table_metadata');
    }
};
