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
        Schema::create('break_times', function (Blueprint $table) {
            $table->id();
            $table->string('break_work_pattern_cd');
            $table->time('break_start_time');
            $table->time('break_end_time');
            $table->unsignedBigInteger('break_organization')->nullable();
            $table->string('break_name')->nullable();
            $table->time('break_start_time1');
            $table->time('break_end_time1');
            $table->time('break_start_time2')->nullable();
            $table->time('break_end_time2')->nullable();
            $table->time('break_start_time3')->nullable();
            $table->time('break_end_time3')->nullable();
            $table->foreign('break_organization')->references('id')->on('organizations')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('break_times');
    }
};
