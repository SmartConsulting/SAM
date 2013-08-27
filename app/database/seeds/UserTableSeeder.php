<?php

class UserTableSeeder extends Seeder {
	public function run()
    {
        DB::table('users')->delete();

        User::add('nolan@smartgroupinc.ca', 'waffles');
    }
}