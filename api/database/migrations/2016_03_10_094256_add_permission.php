<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $perm_id=DB::table('permissions')->insertGetId(
            array('name' => 'add_gallery','display_name' => 'add_gallery')
        );
        DB::table('permission_role')->insert(
            array('permission_id' =>$perm_id,'role_id' => 1)
        );

        $perm_id=DB::table('permissions')->insertGetId(
            array('name' => 'edit_gallery','display_name' => 'edit_gallery')
        );
        DB::table('permission_role')->insert(
            array('permission_id' =>$perm_id,'role_id' => 1)
        );

        $perm_id=DB::table('permissions')->insertGetId(
            array('name' => 'view_gallery','display_name' => 'view_gallery')
        );
        DB::table('permission_role')->insert(
            array('permission_id' =>$perm_id,'role_id' => 1)
        );

        $perm_id=DB::table('permissions')->insertGetId(
            array('name' => 'delete_gallery','display_name' => 'delete_gallery')
        );
        DB::table('permission_role')->insert(
            array('permission_id' =>$perm_id,'role_id' => 1)
        );

        $perm_id=DB::table('permissions')->insertGetId(
            array('name' => 'view_task','display_name' => 'view_task')
        );
        DB::table('permission_role')->insert(
            array('permission_id' =>$perm_id,'role_id' => 1)
        );

        $perm_id=DB::table('permissions')->insertGetId(
            array('name' => 'add_task','display_name' => 'add_task')
        );
        DB::table('permission_role')->insert(
            array('permission_id' =>$perm_id,'role_id' => 1)
        );

        $perm_id=DB::table('permissions')->insertGetId(
            array('name' => 'delete_task','display_name' => 'delete_task')
        );
        DB::table('permission_role')->insert(
            array('permission_id' =>$perm_id,'role_id' => 1)
        );

        $perm_id=DB::table('permissions')->insertGetId(
            array('name' => 'edit_task','display_name' => 'edit_task')
        );
        DB::table('permission_role')->insert(
            array('permission_id' =>$perm_id,'role_id' => 1)
        );


        /*
         *  add comment permission
         */
        $perm_id=DB::table('permissions')->insertGetId(
            array('name' => 'view_comment','display_name' => 'view_comment')
        );
        // add 'view_comment' permission to 'administrator' role. 'administrator' role id is 1.
        DB::table('permission_role')->insert(
            array('permission_id' =>$perm_id,'role_id' => 1)
        );

        $perm_id=DB::table('permissions')->insertGetId(
            array('name' => 'add_comment','display_name' => 'add_comment')
        );
        // add 'add_comment' permission to 'administrator' role.'administrator' role id is 1.
        DB::table('permission_role')->insert(
            array('permission_id' =>$perm_id,'role_id' => 1)
        );

        $perm_id=DB::table('permissions')->insertGetId(
            array('name' => 'edit_comment','display_name' => 'edit_comment')
        );
        // add 'edit_comment' permission to 'administrator' role.'administrator' role id is 1.
        DB::table('permission_role')->insert(
            array('permission_id' =>$perm_id,'role_id' => 1)
        );

        $perm_id=DB::table('permissions')->insertGetId(
            array('name' => 'delete_comment','display_name' => 'delete_comment')
        );
        // add 'delete_comment' permission to 'administrator' role. 'administrator' role id is 1.
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
