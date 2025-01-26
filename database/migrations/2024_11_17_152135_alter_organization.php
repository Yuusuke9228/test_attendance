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
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('organization_zipcode')->nullable()->after('organization_name');
            $table->string('organization_address')->nullable()->after('organization_name');
            $table->string('organization_master_name')->nullable()->after('organization_name');
            $table->unsignedBigInteger('organization_parent_id')->nullable()->after('organization_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('organizations', function(Blueprint $table) {
            $table->dropColumn('organization_zipcode');
            $table->dropColumn('organization_address');
            $table->dropColumn('organization_master_name');
            $table->dropColumn('organization_parent_id');
        });
    }
};
