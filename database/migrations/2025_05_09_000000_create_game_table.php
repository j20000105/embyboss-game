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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('type')->comment('游戏类型');
            $table->string('status')->comment('游戏状态');
            $table->text('details')->comment('游戏详情');
            $table->unsignedBigInteger('total_players')->default(0);
            $table->unsignedBigInteger('total_coins')->default(0);
            $table->unsignedBigInteger('creator_tg_id');
            $table->string('win_details')->nullable()->comment('胜利结果');
            $table->unsignedBigInteger('winner_count')->default(0);
            $table->timestamps();

            $table->index(['type', 'status']);
        });

        Schema::create('game_plays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id');
            $table->unsignedBigInteger('tg_id');
            $table->string('tg_name')->nullable();
            $table->unsignedBigInteger('coins');
            $table->unsignedBigInteger('before_coins');
            $table->unsignedBigInteger('after_coins');
            $table->text('details')->comment('参与详情');
            $table->timestamps();

            $table->index(['game_id', 'tg_id']);
            $table->index('tg_id');
        });

        Schema::create('game_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id');
            $table->unsignedBigInteger('tg_id');
            $table->unsignedBigInteger('coins');
            $table->unsignedBigInteger('before_coins');
            $table->unsignedBigInteger('after_coins');
            $table->text('details')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'tg_id']);
            $table->index('tg_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
        Schema::dropIfExists('game_plays');
        Schema::dropIfExists('game_rewards');
    }
};
