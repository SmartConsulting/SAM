<?php

class AssetTypeTableSeeder extends Seeder {
	public function run()
    {
        DB::table('asset_types')->delete();

        AssetType::create(array( 'name' => 'desktop' ));
        AssetType::create(array( 'name' => 'laptop' ));
        AssetType::create(array( 'name' => 'software' ));

    }
}