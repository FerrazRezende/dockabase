<?php

declare(strict_types=1);

use App\Enums\UserActivityTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_activities', function (Blueprint $table) {
            $table->char('id', 27)->primary();
            $table->char('user_id', 27);
            $table->enum('activity_type', [
                UserActivityTypeEnum::StatusChanged->value,
                UserActivityTypeEnum::DatabaseCreated->value,
                UserActivityTypeEnum::CredentialCreated->value,
                UserActivityTypeEnum::PageView->value,
            ]);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'idx_user_created');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};
