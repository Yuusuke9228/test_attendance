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
    // https://github.com/CryAmir1120/nanaki_attendance/issues/37
    public function up()
    {
        Schema::table('dakou_children', function(Blueprint $table) {
            $table->tinyInteger('dp_unique_counter')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dakou_children', function(Blueprint $table) {
            $table->dropColumn('dp_unique_counter');
        });
    }
};
