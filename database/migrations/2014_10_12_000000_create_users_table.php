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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment("ユーザーコード");
            $table->string('name')->comment("ユーザー名");
            $table->string('email')->unique()->comment("メールアドレス");
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->comment("パスワード");
            $table->integer('role')->comment("ユーザーロール");
            $table->integer('status')->comment("ユーザー登録状態");
            $table->boolean('available')->comment("アクセス許可");
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement("ALTER TABLE users COMMENT 'ユーザー'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
