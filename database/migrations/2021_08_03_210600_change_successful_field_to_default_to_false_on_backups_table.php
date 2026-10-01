<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeSuccessfulFieldToDefaultToFalseOnBackupsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->boolean('is_successful')->after('uuid')->default(false)->change();
        });

         
        DB::table('backups')->select('id')->where('is_successful', 1)->whereNull('completed_at')->update([
            'is_successful' => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->boolean('is_successful')->after('uuid')->default(true)->change();
        });
    }
}
