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
        Schema::create('supported_companies', function (Blueprint $table) {
            $table->id();
            $table->string('supported_company_name');
            $table->string('supported_company_person')->nullable();
            $table->string('supported_company_email')->nullable();
            $table->string('supported_company_tel')->nullable();
            $table->string('supported_company_zipcode')->nullable();
            $table->string('supported_company_address')->nullable();
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
        Schema::dropIfExists('supported_companies');
    }
};
