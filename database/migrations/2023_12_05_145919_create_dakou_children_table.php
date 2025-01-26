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
        Schema::create('dakou_children', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dp_dakoku_id');
            $table->unsignedBigInteger('dp_occupation_id');
            $table->unsignedBigInteger('dp_timezone_id')->nullable();
            $table->unsignedBigInteger('dp_work_contens_id')->nullable();
            $table->unsignedBigInteger('dp_work_location_id')->nullable();
            $table->integer('dp_support_flg')->nullable();
            $table->unsignedBigInteger('dp_support_company_id')->nullable();
            $table->unsignedBigInteger('dp_supported_company_id')->nullable();
            $table->integer('dp_nums_of_people')->nullable();
            $table->unsignedBigInteger('dp_workers_master')->nullable();
            $table->json('dp_workers')->nullable();
            $table->foreign('dp_dakoku_id')->references('id')->on('dakou_data')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('dp_occupation_id')->references('id')->on('occupations')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('dp_work_contens_id')->references('id')->on('work_contents')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('dp_work_location_id')->references('id')->on('work_locations')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('dp_support_company_id')->references('id')->on('support_companies')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('dp_supported_company_id')->references('id')->on('supported_companies')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('dp_workers_master')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('dp_timezone_id')->references('id')->on('time_zones')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dakou_children');
    }
};
