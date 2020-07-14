<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MongodbDsnBreakout extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mongodb_config', function (Blueprint $table) {
            $table->string('host')->after('service_id')->nullable();
            $table->integer('port')->after('host')->nullable();
            $table->string('database')->after('port')->nullable();
            $table->text('username')->after('database')->nullable();
            $table->text('password')->after('username')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mongodb_config', function (Blueprint $table) {
            $table->dropColumn(['host','port','database','username','password']);
        });
    }
}
