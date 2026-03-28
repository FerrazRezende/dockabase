<?php

use App\Enums\CredentialPermissionEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credentials', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 255);
            $table->enum('permission', [
                CredentialPermissionEnum::Read->value,
                CredentialPermissionEnum::Write->value,
                CredentialPermissionEnum::ReadWrite->value,
            ])->default(CredentialPermissionEnum::Read->value);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credentials');
    }
};
