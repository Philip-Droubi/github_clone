<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('users')->delete();
        
        \DB::table('users')->insert(array (
            0 => 
            array (
                'id' => 1,
                'role' => 1,
                'first_name' => 'Salem',
                'last_name' => 'Omar',
                'account_name' => 'omarfadi',
                'email' => 'email@email.com',
                'email_verified_at' => NULL,
                'password' => '$2y$12$kcdpQzK53TohPTCcRa9rnu.o2c1DQvjeM30S8S7fcDRWmbHGpPVKO',
                'last_seen' => '2023-11-08 19:08:26',
                'img' => 'defaults/default_user.jpg',
                'remember_token' => NULL,
                'created_at' => '2023-11-08 19:08:26',
                'updated_at' => '2023-11-08 19:08:26',
            ),
        ));
        
        
    }
}