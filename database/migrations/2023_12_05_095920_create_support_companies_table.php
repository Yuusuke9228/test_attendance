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
        Schema::create('support_companies', function (Blueprint $table) {
            $table->id();
            $table->string('support_company_name');
            $table->string('support_company_person')->nullable();
            $table->string('support_company_email')->nullable();
            $table->string('support_company_tel')->nullable();
            $table->string('support_company_zipcode')->nullable();
            $table->string('support_company_address')->nullable();
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
        Schema::dropIfExists('support_companies');
    }
};
