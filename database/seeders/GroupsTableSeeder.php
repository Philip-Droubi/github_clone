<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class GroupsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('groups')->delete();
        
        \DB::table('groups')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'public group',
                'description' => 'this group is created to share files in public between all users, where anyone can access anybody  files.',
                'group_key' => 'hdxPhPu98MbLUsxtguZDWSLrqkTIC77C',
                'is_public' => 1,
                'created_by' => 1,
                'created_at' => '2023-11-22 18:12:39',
                'updated_at' => '2023-11-25 00:14:10',
            ),
        ));
        
        
    }
}