<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credential_user', function (Blueprint $table) {
            $table->id();
            $table->char('credential_id', 27);
            $table->char('user_id', 36);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('credential_id')->references('id')->on('credentials')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['credential_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_user');
    }
};
