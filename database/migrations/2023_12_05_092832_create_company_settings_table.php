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
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('company_kana')->nullable();
            $table->string('company_zip_code')->nullable();
            $table->string('company_tel01')->nullable();
            $table->string('company_tel02')->nullable();
            $table->string('company_tel03')->nullable();
            $table->string('company_fax01')->nullable();
            $table->string('company_fax02')->nullable();
            $table->string('company_fax03')->nullable();
            $table->unsignedBigInteger('company_pref')->nullable();
            $table->string('company_addr01')->nullable();
            $table->string('company_addr02')->nullable();
            $table->string('company_bank_name')->nullable();
            $table->string('company_bank_office_no')->nullable();
            $table->string('company_bank_bank_account_type')->nullable();
            $table->string('company_bank_bank_account_no')->nullable();
            $table->foreign('company_pref')->references('id')->on('prefecture_lists')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('company_settings');
    }
};
