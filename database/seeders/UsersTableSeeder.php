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
                'password' => '$2y$12$XizXppgZDV/5TINcNd1x5.J38VVqTOC4d1/V3CXWX9/kDJtFdUaWe',
                'last_seen' => '2023-11-10 19:01:38',
                'img' => NULL,
                'remember_token' => NULL,
                'created_at' => '2023-11-09 17:53:01',
                'updated_at' => '2023-11-10 19:01:38',
            ),
            1 => 
            array (
                'id' => 2,
                'role' => 2,
                'first_name' => 'Rami',
                'last_name' => 'Sami',
                'account_name' => 'omarfadi3',
                'email' => 'email3@email.com',
                'email_verified_at' => NULL,
                'password' => '$2y$12$9ARg3/jVQ9T.W9N8ufqMCOthdzEGFzobCfgxnlbSB8aAfZCtRpZPS',
                'last_seen' => '2023-11-10 17:56:52',
                'img' => 'users/2/profile_images/LVfwQVJseQJrA84eJeQEo9xfLJ2n5jistockphoto-489171250-1024x1024.jpg',
                'remember_token' => NULL,
                'created_at' => '2023-11-09 17:53:59',
                'updated_at' => '2023-11-10 17:56:52',
            ),
        ));
        
        
    }
}