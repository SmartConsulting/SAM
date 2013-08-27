<?php

class AssetTableSeeder extends Seeder {
	public function run()
    {
        DB::table('assets')->delete();

        Asset::create(array(
        	'role_id'       => DB::table('roles')->where('name', 'FNTCBCKAMWKS21')->pluck('id'),
        	'asset_type_id' => Asset::findType('desktop', 'id'),
        	'ownership'     => Asset::PURCHASE,
        	'maker'         => 'Lenovo',
        	'product'       => 'ThinkCentre M31',
        	'purchase_year' => 2011,
        	'purchase_cost' => 2300.00,
        	'replace_cost'  => 2300.00,
        	'lifespan'      => 3
        ));

        Asset::create(array(
        	'role_id'       => DB::table('roles')->where('name', 'FNTCBCKAMLAP01')->pluck('id'),
        	'asset_type_id' => Asset::findType('laptop', 'id'),
        	'ownership'     => Asset::PURCHASE,
        	'maker'         => 'Lenovo',
        	'product'       => 'ThinkPad T430s',
        	'purchase_year' => 2013,
        	'purchase_cost' => 2500.00,
        	'replace_cost'  => 2500.00,
        	'lifespan'      => 3
        ));

        Asset::create(array(
        	'role_id'       => DB::table('roles')->where('name', 'PDF Editor')->pluck('id'),
        	'asset_type_id' => Asset::findType('software', 'id'),
        	'ownership'     => Asset::LICENSE,
        	'maker'         => 'Adobe',
        	'product'       => 'Creative Cloud',
        	'purchase_year' => 2012,
        	'purchase_cost' => 55.00,
        	'replace_cost'  => 55.00,
        	'lifespan'      => 1,
        	'recurring'     => true
        ));


    }
}