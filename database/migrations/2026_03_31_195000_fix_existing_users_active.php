<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('active')
            ->update(['active' => true]);
        
        DB::table('users')
            ->whereNull('password_changed_at')
            ->update(['password_changed_at' => now()]);
    }

    public function down(): void
    {
        // No rollback needed for this fix
    }
};
