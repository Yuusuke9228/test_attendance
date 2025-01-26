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
        Schema::table('company_settings', function(Blueprint $table) {
            $table->boolean('company_month_closing_status')->default(false);
            $table->date('company_month_closing_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_settings', function(Blueprint $table) {
            $table->dropColumn('company_month_closing_status');
            $table->dropColumn('company_month_closing_date');
        });
    }
};
