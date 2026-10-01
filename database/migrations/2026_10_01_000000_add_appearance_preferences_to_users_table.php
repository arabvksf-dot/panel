<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('appearance')->nullable();
            $table->boolean('onboarding_completed')->default(false);
        });

        DB::table('users')->whereNull('appearance')->update([
            'appearance' => json_encode([
                'theme' => 'dark',
                'accent' => 'violet',
                'motion' => true,
                'font_size' => 16,
            ], JSON_THROW_ON_ERROR),
        ]);

        DB::table('users')->update(['onboarding_completed' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['appearance', 'onboarding_completed']);
        });
    }
};