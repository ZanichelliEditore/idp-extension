<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeGrantsColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('grants', function (Blueprint $table) {
            $table->dropColumn('role_id');
            $table->dropColumn('department_id');
            // INFO : Create new columns
            $table->string('role_name', 50)->unique();
            $table->string('department_name', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('grants', function (Blueprint $table) {
            $table->dropColumn('role_name');
            $table->dropColumn('department_name');
            // INFO : Create new columns
            $table->integer('role_id');
            $table->integer('department_id')->nullable();
        });
    }
}
