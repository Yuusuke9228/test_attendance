<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dakou_data', function (Blueprint $table) {
            $table->id();
            $table->date('target_date');
            $table->unsignedBigInteger('dp_user');
            $table->unsignedBigInteger('dp_status');
            $table->time('dp_syukkin_time')->nullable();
            $table->time('dp_taikin_time')->nullable();
            $table->time('dp_break_start_time')->nullable();
            $table->time('dp_break_end_time')->nullable();
            $table->time('dp_gaishutu_start_time')->nullable();
            $table->time('dp_gaishutu_end_time')->nullable();
            $table->string('dp_ride_flg')->nullable();
            $table->unsignedBigInteger('dp_other_flg')->nullable();
            $table->text('dp_memo')->nullable();
            $table->string('dp_dakou_address')->nullable();
            $table->unsignedBigInteger('dp_made_by')->nullable();
            $table->foreign('dp_user')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('dp_status')->references('id')->on('attend_types')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('dp_other_flg')->references('id')->on('attend_statuses')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('dp_made_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
            $table->softDeletes();

            // dp_type column added via modify_dakou_data migration file
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dakou_data');
    }
};
