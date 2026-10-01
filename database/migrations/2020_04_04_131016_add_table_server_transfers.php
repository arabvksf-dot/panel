<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTableServerTransfers extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         
         
        Schema::dropIfExists('server_transfers');

        Schema::create('server_transfers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('server_id')->unsigned();
            $table->boolean('successful')->unsigned()->default(0);
            $table->integer('old_node')->unsigned();
            $table->integer('new_node')->unsigned();
            $table->integer('old_allocation')->unsigned();
            $table->integer('new_allocation')->unsigned();
            $table->json('old_additional_allocations')->nullable();
            $table->json('new_additional_allocations')->nullable();
            $table->timestamps();

            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_transfers');
    }
}
