<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_schema_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('database_id')->constrained()->cascadeOnDelete();
            $table->string('action', 50);
            $table->string('table_name', 255)->nullable();
            $table->string('column_name', 255)->nullable();
            $table->jsonb('old_value')->nullable();
            $table->jsonb('new_value')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['database_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_schema_histories');
    }
};
