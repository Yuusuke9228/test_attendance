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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_person')->nullable();
            $table->string('customer_saluation')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_tel')->nullable();
            $table->string('customer_fax')->nullable();
            $table->string('customer_zip_code')->nullable();
            $table->string('customer_address_1')->nullable();
            $table->string('customer_address_2')->nullable();
            $table->text('customer_memo')->nullable();
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
        Schema::dropIfExists('customers');
    }
};
