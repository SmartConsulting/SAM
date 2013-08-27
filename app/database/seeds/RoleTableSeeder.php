<?php

class RoleTableSeeder extends Seeder {
	public function run()
    {
        DB::table('roles')->delete();

        Role::create(array(
        	'name' => 'FNTCBCKAMWKS21',
        	'user' => 'Nolan',
        	'location' => 'Kamloops'
        ));

        Role::create(array(
        	'name' => 'FNTCBCKAMLAP01',
        	'user' => 'Nicole',
        	'location' => 'Kamloops'
        ));

        Role::create(array(
        	'name' => 'PDF Editor',
        	'user' => 'Derek',
        	'location' => 'Kamloops'
        ));
    }
}