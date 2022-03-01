<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeGrantsTableColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // INFO : Create a backup table
        Schema::create('grants_backup', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('role_id');
            $table->integer('department_id')->nullable();
            $table->text('grant');
        });
        DB::statement('INSERT grants_backup SELECT * FROM grants');

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
        Schema::dropIfExists('grants');
        Schema::rename("grants_backup", "grants");
    }
}
