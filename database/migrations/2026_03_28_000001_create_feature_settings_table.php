<?php

use App\Enums\RolloutStrategyEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('feature_name')->unique();
            $table->string('strategy')->default(RolloutStrategyEnum::Inactive->value);
            $table->unsignedInteger('percentage')->default(0);
            $table->json('user_ids')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index('feature_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_settings');
    }
};
