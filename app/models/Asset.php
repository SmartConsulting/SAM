<?php

class Asset extends Eloquent {
	
	protected $table = 'assets';
	public $timestamps = false;
	protected $guarded = array('id');
	protected $softDelete = true;

	const PURCHASE = 1;
	const LEASE    = 2;
	const LICENSE  = 3;

	private static $_ownership_types = array(
		1 => 'purchase',
		2 => 'lease',
		3 => 'license'
	);

	public static function ownershipTypes() {
		return self::$_ownership_types;
	}
	public function getOwnershipAttribute() {
		return static::$_ownership_types[$this->attributes['ownership']];
	}
	public function getOwnershipIdAttribute() {
		return $this->attributes['ownership'];
	}

	public static function findType($name, $attribute = null) {
		if (is_null($attribute))
			return AssetType::where('name', $name)->first();
		else
			return DB::table('asset_types')->where('name', $name)->pluck($attribute);
	}

	public function role() {
		return $this->belongsTo('Role');
	}

	public function type() {
		return $this->belongsTo('AssetType', 'asset_type_id');
	}

	public function getActiveAttribute() {
		$now = Carbon\Carbon::now(Config::get('app.timezone', 'UTC'));
		$fiscal = $now->year;
		if ($now->month < 4)
			$fiscal--;

		return ($this->purchase_year <= $fiscal) && ($this->purchase_year + $this->lifespan > $fiscal);
	}

}