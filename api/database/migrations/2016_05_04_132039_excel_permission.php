<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ExcelPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $perm_id=DB::table('permissions')->insertGetId(
            array('name' => 'export_pdf','display_name' => 'export_pdf')
        );
        DB::table('permission_role')->insert(
            array('permission_id' =>$perm_id,'role_id' => 1)
        );
        $perm_id=DB::table('permissions')->insertGetId(
            array('name' => 'import_user','display_name' => 'import_user')
        );
        DB::table('permission_role')->insert(
            array('permission_id' =>$perm_id,'role_id' => 1)
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
