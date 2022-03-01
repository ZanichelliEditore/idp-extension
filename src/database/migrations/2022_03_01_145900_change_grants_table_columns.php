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
        DB::statement('CREATE TABLE grants_backup LIKE grants;');
        DB::statement('INSERT new_table SELECT * FROM old_table');
        
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
