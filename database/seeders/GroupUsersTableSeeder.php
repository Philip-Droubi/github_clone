<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class GroupUsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('group_users')->delete();

        \DB::table('group_users')->insert(array(
            0 =>
            array(
                'id' => 1,
                'group_id' => 1,
                'user_id' => 1,
                'removed_at' => NULL,
                'created_at' => '2023-11-22 18:12:39',
                'updated_at' => '2023-11-22 18:12:39',
            ),
            1 =>
            array(
                'id' => 2,
                'group_id' => 1,
                'user_id' => 2,
                'removed_at' => NULL,
                'created_at' => '2023-11-22 18:12:39',
                'updated_at' => '2023-11-22 18:12:39',
            ),
        ));
    }
}
