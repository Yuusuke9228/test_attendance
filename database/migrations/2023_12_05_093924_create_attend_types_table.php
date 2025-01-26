<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::create('attend_types', function (Blueprint $table) {
            $table->id();
            $table->string('attend_type_name')->comment("打刻名");
            $table->timestamps();
        });

        DB::statement("ALTER TABLE attend_types COMMENT '打刻区分'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attend_types');
    }
};
