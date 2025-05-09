<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (config('app.env') !== 'testing') {
            return;
        }
        Schema::create('emby', function (Blueprint $table) {
            $table->unsignedBigInteger('tg');
            $table->integer('iv');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('app.env') !== 'testing') {
            return;
        }
        Schema::dropIfExists('emby');
    }
};
