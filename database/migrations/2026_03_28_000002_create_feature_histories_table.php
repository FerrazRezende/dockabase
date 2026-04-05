<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('feature_setting_id')->constrained('feature_settings')->cascadeOnDelete();
            $table->string('action'); // activated, deactivated, updated
            $table->char('actor_id', 27);
            $table->json('previous_state')->nullable();
            $table->json('new_state')->nullable();
            $table->timestamps();

            $table->foreign('actor_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['feature_setting_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_histories');
    }
};
