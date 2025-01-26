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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->json('user_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->date('schedule_date');
            $table->time('schedule_start_time')->nullable();
            $table->time('schedule_end_time')->nullable();
            $table->unsignedBigInteger('occupation_id')->nullable();
            $table->foreign('location_id')->references('id')->on('work_locations')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('occupation_id')->references('id')->on('occupations')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('schedules');
    }
};
