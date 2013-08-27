<?php

View::composer('layout.main', function($view) {
	$segments = explode('/', Request::path());
	array_pop($segments);
	$breadcrumbs = array();
	$slug = '';

	foreach ($segments as $segment) {
		if ($slug != '')
			$slug .= '/';
		$slug .= Str::plural($segment);
		$breadcrumbs[$slug] = ucwords(Str::plural($segment));
	}
  $view->with('breadcrumbs', $breadcrumbs);

  $types = array();
  foreach(DB::table('asset_types')->distinct()->where('deleted_at', null)->orderBy('name')->lists('name') as $type)
  	$types['assets/'.Str::slug(str_plural($type))] = str_plural($type);
  $view->with('types', $types);

  $role_users = array();
  foreach(DB::table('roles')->distinct()->where('deleted_at', null)->orderBy('user')->lists('user') as $user)
  	$role_users['roles/'.Str::slug($user)] = $user;
  $view->with('role_users', $role_users);


  $now = Carbon\Carbon::now(Config::get('app.timezone', 'UTC'));
	$fiscal = $now->year;
	if ($now->month < 4)
		$fiscal--;
	$view->with('fiscal_year', $fiscal);

	if (Session::has('alert-message')) {
		$alert = new stdClass;
		$alert->type = Session::get('alert-type', 'warning');
		$alert->title = Session::get('alert-title', '');
		$alert->message  = Session::get('alert-message');
		$alert->dismiss = Session::get('alert-dismiss', true);

		$view->with('alert', $alert);
	}

});

View::composer('login', function($view) {
	if (Session::has('alert-message')) {
		$alert = new stdClass;
		$alert->type = Session::get('alert-type', 'warning');
		$alert->title = Session::get('alert-title', '');
		$alert->message  = Session::get('alert-message');
		$alert->dismiss = Session::get('alert-dismiss', true);

		$view->with('alert', $alert);
	}
});

View::composer('asset.form', function($view) {
	$view->with('roles', Role::all())
	     ->with('types', AssetType::all())
	     ->with('ownership_types', Asset::ownershipTypes());
});

View::composer('role.form', function($view) {
	$role_users = '"'.implode('","', DB::table('roles')->distinct()->where('deleted_at', null)->orderBy('user')->lists('user')).'"';
	$locations = '"'.implode('","', DB::table('roles')->distinct()->where('deleted_at', null)->orderBy('location')->lists('location')).'"';

	$view->with('role_users', $role_users)
	     ->with('locations', $locations);
});



Route::get('/', function() {
	return Redirect::to('assets');
});

Route::get('login', function() {
	return View::make('login')->with('title', 'Smart - Login');
});

Route::post('login', function() {
	$authenticated = Auth::attempt(Input::only('email', 'password'), Input::has('remember'));
	if ($authenticated)
    return Redirect::intended('roles');
 	else
 		return Redirect::to('login')->with('alert-type', 'warning')
		                            ->with('alert-title', 'Login Failed')
		                            ->with('alert-message', 'email address or password not found');


});

Route::group(array('before' => 'auth'), function() {

	/*
	|--------------------------------------------------------------------------
	| Asset Routes
	|--------------------------------------------------------------------------
	*/

	// List
	Route::get('assets', function() {
		return View::make('asset.list')->with('title', 'Assets')
		                               ->with('assets', Asset::with('type', 'role')->get());
	});

	// New
	Route::get('assets/new', function() {
		return View::make('asset.edit')->with('title', 'New Asset');
	});

	// Save New
	Route::post('asset', function() {
		Asset::create(array(
			'role_id'       => Input::get('role'),
	  	'asset_type_id' => Input::get('type'),
	  	'ownership'     => Input::get('ownership'),
	  	'maker'         => Input::get('maker', null),
	  	'product'       => Input::get('product', null),
	  	'model'         => Input::get('model', null),
	  	'serial'        => Input::get('serial', null),
	  	'license'       => Input::get('license', null),
	  	'purchase_year' => Input::get('purchase_year'),
	  	'purchase_cost' => Input::get('purchase_cost'),
	  	'replace_cost'  => Input::get('replace_cost'),
	  	'lifespan'      => Input::get('lifespan'),
	  	'recurring'     => Input::get('ownership') == 3
		));
		return Redirect::to('assets/new')->with('alert-type', 'success')
		                                 ->with('alert-title', 'Created')
		                                 ->with('alert-message', 'New asset successfully created.');
	});

	// Edit
	Route::get('asset/{id}', function($id) {
		$asset = Asset::findOrFail($id);
		if (Request::ajax())
			return View::make('asset.form')->with('asset', $asset);
		else
			return View::make('asset.edit')->with('title', $asset->product)
			                               ->with('asset', $asset);
	})->where('id', '[0-9]+');
	// Save Edit
	Route::post('asset/{id}', function($id) {
		$asset = Asset::findOrFail($id);
		$asset->role_id       = Input::get('role');
		$asset->asset_type_id = Input::get('type');
		$asset->ownership     = Input::get('ownership');
		$asset->maker         = Input::get('maker', null);
		$asset->product       = Input::get('product', null);
		$asset->model         = Input::get('model', null);
		$asset->serial        = Input::get('serial', null);
		$asset->license       = Input::get('license', null);
		$asset->purchase_year = Input::get('purchase_year');
		$asset->purchase_cost = Input::get('purchase_cost');
		$asset->replace_cost  = Input::get('replace_cost');
		$asset->lifespan      = Input::get('lifespan');
		$asset->recurring     = (Input::get('ownership') == 3);
		$asset->save();

		return Redirect::to(Session::get('last-filter', 'assets'))->with('alert-type', 'info')
		                                                       ->with('alert-title', 'Updated')
		                                                       ->with('alert-message', 'Asset successfully updated.');
	})->where('id', '[0-9]+');

	// Delete
	Route::get('asset/{id}/delete', function($id) {
		$asset = Asset::findOrFail($id);
		$asset->delete();
		return Redirect::to(Session::get('last-filter', 'assets'))->with('alert-type', 'error')
		                                                          ->with('alert-title', 'Deleted')
		                                                          ->with('alert-message', 'Asset successfully deleted. <a class="btn btn-mini btn-danger" href="'.URL::to('asset/'.$id.'/restore').'">Undo</a>');
	})->where('id', '[0-9]+');

	// Restore
	Route::get('asset/{id}/restore', function($id) {
		Asset::onlyTrashed()->where('id', $id)->restore();
		return Redirect::to(Session::get('last-filter', 'assets'))->with('alert-type', 'warning')
		                                                          ->with('alert-title', 'Restored')
		                                                          ->with('alert-message', 'Asset successfully restored.');
	})->where('id', '[0-9]+');

	// List by Type
	Route::get('assets/{type_name}', function($type_name) {
		$type = AssetType::with('assets', 'assets.role')->where('name', str_singular(str_replace('-', ' ', $type_name)))->firstOrFail();
		return View::make('asset.list')->with('title', ucwords($type_name))
		                               ->with('assets', $type->assets);
	})->where('type_name', '[A-Za-z\-]+');


	/*
	|--------------------------------------------------------------------------
	| Role Routes
	|--------------------------------------------------------------------------
	*/

	// List
	Route::get('roles', function() {
		$now = Carbon\Carbon::now(Config::get('app.timezone', 'UTC'));
		$fiscal = $now->year;
		if ($now->month < 4)
			$fiscal--;

		$roles = Role::with(array('assets' => function($query) use ($fiscal) {
			$query->where('recurring', 1)
			      ->orWhere(function($query) use ($fiscal) {
				$query->where('purchase_year', '<=', $fiscal)
				      ->whereRaw('purchase_year + lifespan > ?', array($fiscal));
			});
		}))->get();
		$roles->load('assets.type');
		return View::make('role.list')->with('title', 'Roles')
		                              ->with('roles', $roles);
	});

	// New
	Route::get('roles/new', function() {
		return View::make('role.edit')->with('title', 'New Role');
	});

	// Save New
	Route::post('role', function() {
		Role::create(array(
			'name'     => Input::get('name'),
	  	'user'     => Input::get('user', null),
	  	'location' => Input::get('location', null)
		));
		return Redirect::to('roles/new')->with('alert-type', 'success')
		                                ->with('alert-title', 'Created')
		                                ->with('alert-message', 'New role successfully created.');
	});

	// Edit
	Route::get('role/{id}', function($id) {
		$role = Role::findOrFail($id);
		if (Request::ajax())
			return View::make('role.form')->with('role', $role);
		else
			return View::make('role.edit')->with('title', $role->name)
			                              ->with('role', $role);
	})->where('id', '[0-9]+');
	// Save Edit
	Route::post('role/{id}', function($id) {
		$role = Role::findOrFail($id);
		$role->name     = Input::get('name');
		$role->user     = Input::get('user');
		$role->location = Input::get('location');
		$role->save();

		return Redirect::to(Session::get('last-filter', 'role'))->with('alert-type', 'info')
		                                                     ->with('alert-title', 'Updated')
		                                                     ->with('alert-message', 'Role successfully updated.');
	})->where('id', '[0-9]+');

	// Delete
	Route::get('role/{id}/delete', function($id) {
		$role = Role::findOrFail($id);
		$role->delete();
		return Redirect::to(Session::get('last-filter', 'role'))->with('alert-type', 'error')
		                                                     ->with('alert-title', 'Deleted')
		                                                     ->with('alert-message', 'Role successfully deleted. <a class="btn btn-mini btn-danger" href="'.URL::to('role/'.$id.'/restore').'">Undo</a>');
	})->where('id', '[0-9]+');

	// Restore
	Route::get('role/{id}/restore', function($id) {
		Role::onlyTrashed()->where('id', $id)->restore();
		return Redirect::to(Session::get('last-filter', 'role'))->with('alert-type', 'warning')
		                                                     ->with('alert-title', 'Restored')
		                                                     ->with('alert-message', 'Role successfully restored.');
	})->where('id', '[0-9]+');

	// List by User
	Route::get('roles/{user}', function($user) {
		$user = str_replace('-', ' ', $user);

		$now = Carbon\Carbon::now(Config::get('app.timezone', 'UTC'));
		$fiscal = $now->year;
		if ($now->month < 4)
			$fiscal--;

		$roles = Role::with(array('assets' => function($query) use ($fiscal) {
			$query->where('recurring', 1)
			      ->orWhere(function($query) use ($fiscal) {
				$query->where('purchase_year', '<=', $fiscal)
				      ->whereRaw('purchase_year + FLOOR(lifespan / 12) > '.$fiscal);
			});
		}))->where(DB::raw('LOWER(`user`)'), $user)->get();

		return View::make('role.list')->with('title', 'Used by '.$roles[0]->user)
		                              ->with('roles', $roles);
	})->where('type_name', '[A-Za-z]+');



	/*
	|--------------------------------------------------------------------------
	| Type Routes
	|--------------------------------------------------------------------------
	*/

	Route::get('types', function() {
		return View::make('type.list')->with('title', 'Types')
		                              ->with('types', AssetType::with('assets')->get());
	});

	// New
	Route::get('types/new', function() {
		return View::make('type.new')->with('title', 'New Type');
	});

	// Save New
	Route::post('type', function() {
		AssetType::create(array(
			'name'     => Input::get('name')
		));
		return Redirect::to('types/new')->with('alert-type', 'success')
		                                ->with('alert-title', 'Created')
		                                ->with('alert-message', 'New type successfully created.');
	});

	// Edit
	Route::get('type/{id}', function($id) {
		$type = AssetType::findOrFail($id);
		return View::make('type.form')->with('title', $type->name)
		                              ->with('type', $type);
	})->where('id', '[0-9]+');
	// Save Edit
	Route::post('type/{id}', function($id) {
		$type = AssetType::findOrFail($id);
		$type->name     = Input::get('name');
		$type->save();

		return Redirect::to('types')->with('alert-type', 'info')
		                             ->with('alert-title', 'Updated')
		                             ->with('alert-message', 'Type successfully updated.');
	})->where('id', '[0-9]+');

	// Delete
	Route::get('type/{id}/delete', function($id) {
		$type = AssetType::findOrFail($id);
		$type->delete();
		return Redirect::to('types')->with('alert-type', 'error')
		                            ->with('alert-title', 'Deleted')
		                            ->with('alert-message', 'Type successfully deleted. <a class="btn btn-mini btn-danger" href="'.URL::to('role/'.$id.'/restore').'">Undo</a>');
	})->where('id', '[0-9]+');

	// Restore
	Route::get('type/{id}/restore', function($id) {
		Type::onlyTrashed()->where('id', $id)->restore();
		return Redirect::to('roles')->with('alert-type', 'warning')
		                            ->with('alert-title', 'Restored')
		                            ->with('alert-message', 'Type successfully restored.');
	})->where('id', '[0-9]+');



	/*
	|--------------------------------------------------------------------------
	| Report Routes
	|--------------------------------------------------------------------------
	*/

	Route::get('reports/purchases/{year}/{output?}', function($year, $output = 'html') {
		
		$alert = new stdClass;
		$alert->type = 'success';
		$alert->title = '<i class="icon icon-white icon-download"></i> Download as ';
		$alert->message = '<a class="btn btn-mini btn-success" href="'.URL::to('reports/purchases/'.$year.'/xlsx').'">Excel 2007</a>
		                   <a class="btn btn-mini btn-success" href="'.URL::to('reports/purchases/'.$year.'/xls').'">Excel 2003</a>
		                   <a class="btn btn-mini btn-success" href="'.URL::to('reports/purchases/'.$year.'/csv').'">CSV</a>
		                   <a class="btn btn-mini btn-success" href="'.URL::to('reports/purchases/'.$year.'/pdf').'">PDF</a> |
		                   <a class="btn btn-mini btn-success" onclick="window.print()"><i class="icon icon-white icon-print"></i> Print</a>';
		$alert->dismiss = false;

		$assets = Asset::with('type', 'role')->where('purchase_year', $year)->orderBy('asset_type_id')->get();
		$total = 0;
		$types = array();
		if ($output == 'html') {
			foreach($assets as $asset) {
				$type_id = $asset->asset_type_id;
				if (!array_key_exists($type_id, $types)) {
					$types[$type_id] = new stdClass;
					$types[$type_id]->name = ucwords($asset->type->name);
					$types[$type_id]->total = 0;
					$types[$type_id]->assets = array();
				}
				$types[$type_id]->assets[] = $asset;
				$types[$type_id]->total += $asset->purchase_cost;
				$total += $asset->purchase_cost;
			}
		
			return View::make('report/purchases')->with('title', $year.' Purchases')
			                                     ->with('types', $types)
			                                     ->with('year', $year)
			                                     ->with('total', $total)
			                                     ->with('alert', $alert);
		} else {
			$row = 2;

			$report = new PHPExcel();
			$report->getProperties()->setCreator('Smart Asset Management')
			                            ->setTitle('Smart '.$year.' Purchases');
			$report->setActiveSheetIndex(0)
			       ->setCellValue('A1', 'Type')
			       ->setCellValue('B1', 'Asset')
			       ->setCellValue('C1', 'Role')
			       ->setCellValue('D1', 'Cost');

			foreach($assets as $asset) {
				$report->getActiveSheet()->setCellValue('A'.$row, $asset->type->name)
			                           ->setCellValue('B'.$row, $asset->maker.' '.$asset->product)
			                           ->setCellValue('C'.$row, $asset->role->name)
			                           ->setCellValue('D'.$row, $asset->purchase_cost);
			  $row++;
			}
			$report->getActiveSheet()->setCellValue('C'.$row, 'Total:')
			                         ->setCellValue('D'.$row, '=SUM(D2:D'.($row - 1).')');

			$filename = storage_path().'/'.md5(serialize($assets));

			if ($output == 'xlsx')
				$reportWriter = PHPExcel_IOFactory::createWriter($report, 'Excel2007');
			elseif ($output == 'xls')
				$reportWriter = PHPExcel_IOFactory::createWriter($report, 'Excel5');
			elseif ($output == 'csv')
				$reportWriter = PHPExcel_IOFactory::createWriter($report, 'CSV');
			elseif ($output == 'pdf') {

				if (!PHPExcel_Settings::setPdfRenderer(PHPExcel_Settings::PDF_RENDERER_DOMPDF, base_path().'/vendor/dompdf/dompdf')) {
					die(
						'NOTICE: Please set the $rendererName and $rendererLibraryPath values' .
						'<br />' .
						'at the top of this script as appropriate for your directory structure'
					);
				}

				$reportWriter = PHPExcel_IOFactory::createWriter($report, 'PDF');

			}

			$reportWriter->save($filename);



			return Response::download($filename, 'Smart-'.$year.'-Purchases.'.$output);
		}
	})->where('year', '[0-9]+');

}); // end of auth group

/*Route::get('import', function() {

	$assets = array(
	  array('id'=>1,'asset_type_id'=>1,'name'=>'FNTCBCKAMLAP01','user'=>'Nicole','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2700.00,'invoice_date'=>'1970-01-01','purchase_year'=>2007,'replace_year'=>2013,'replace_cost'=>2700.00,'replace_date'=>'2014-04-17','description'=>'Model: 2623K8U

	Serial: L3BF210','replaced'=>0),
	  array('id'=>2,'asset_type_id'=>1,'name'=>'FNTCBCKAMLAP02','user'=>'Tracey','manufacturer'=>'Toshiba','quantity'=>1,'purchase_cost'=>2700.00,'invoice_date'=>'2011-02-28','purchase_year'=>2010,'replace_year'=>2014,'replace_cost'=>2700.00,'replace_date'=>'2015-02-28','description'=>'Model: PORTEGE A600

	Serial: 4A080216H','replaced'=>0),
	  array('id'=>3,'asset_type_id'=>1,'name'=>'SPARELAP06','user'=>'Infomanager','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2700.00,'invoice_date'=>'2009-03-26','purchase_year'=>2008,'replace_year'=>2012,'replace_cost'=>2700.00,'replace_date'=>'2013-03-26','description'=>'Model: 64781HU

	Serial: L3ABT1K','replaced'=>1),
	  array('id'=>4,'asset_type_id'=>1,'name'=>'SPARELAP10','user'=>'Ken','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2700.00,'invoice_date'=>'2008-05-14','purchase_year'=>2008,'replace_year'=>2011,'replace_cost'=>2700.00,'replace_date'=>'2011-05-14','description'=>'Model: 64781HU

	Serial: L3AAB9W','replaced'=>1),
	  array('id'=>5,'asset_type_id'=>1,'name'=>'FNTCBCKAMLAP11','user'=>'Trenton','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2700.00,'invoice_date'=>'2009-04-09','purchase_year'=>2009,'replace_year'=>2012,'replace_cost'=>2700.00,'replace_date'=>'2012-04-09','description'=>'Model: 64781HU

	Serial: L3ABT1F','replaced'=>1),
	  array('id'=>6,'asset_type_id'=>1,'name'=>'FNTCBCKAMLAP12','user'=>'Sarah','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2700.00,'invoice_date'=>'2009-03-10','purchase_year'=>2008,'replace_year'=>2013,'replace_cost'=>2700.00,'replace_date'=>'2013-04-10','description'=>'Model: 64781HU

	Serial: L3ABT2A','replaced'=>0),
	  array('id'=>7,'asset_type_id'=>1,'name'=>'FNTCBCKAMLAP13','user'=>'Rick','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2700.00,'invoice_date'=>'1970-01-01','purchase_year'=>2008,'replace_year'=>2013,'replace_cost'=>2700.00,'replace_date'=>'2013-04-01','description'=>'Model: 64781HU

	Serial: L3ABT1E','replaced'=>0),
	  array('id'=>8,'asset_type_id'=>3,'name'=>'FNTCBCKAMMGM1','user'=>'Administrator','manufacturer'=>'IBM','quantity'=>1,'purchase_cost'=>0.00,'invoice_date'=>'1970-01-01','purchase_year'=>2010,'replace_year'=>2013,'replace_cost'=>0.00,'replace_date'=>'2014-01-01','description'=>'Model: 8189KUM

	Serial: KCWA47N','replaced'=>0),
	  array('id'=>12,'asset_type_id'=>3,'name'=>'FNTCBCKAMTS1','user'=>'Itsupport','manufacturer'=>'IBM','quantity'=>1,'purchase_cost'=>0.00,'invoice_date'=>'2011-01-01','purchase_year'=>2010,'replace_year'=>2010,'replace_cost'=>0.00,'replace_date'=>'2011-03-01','description'=>'Model: IBM eServer x3500-[7977AC1]-

	Serial: KQXZZT3','replaced'=>0),
	  array('id'=>13,'asset_type_id'=>3,'name'=>'FNTCBCKAMVH1','user'=>'Infomanager','manufacturer'=>'IBM','quantity'=>1,'purchase_cost'=>9500.00,'invoice_date'=>'2009-07-14','purchase_year'=>2009,'replace_year'=>2012,'replace_cost'=>9500.00,'replace_date'=>'2012-06-14','description'=>'Model: IBM System x -[794732U]-

	Serial: 99A0507','replaced'=>0),
	  array('id'=>15,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS03','user'=>'Legal','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'1970-01-01','purchase_year'=>2010,'replace_year'=>2014,'replace_cost'=>2500.00,'replace_date'=>'2015-02-28','description'=>'Model: 0806B3U

	Serial: MJMBBP5','replaced'=>0),
	  array('id'=>16,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS04','user'=>'Marlene','manufacturer'=>'IBM','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2011-01-01','purchase_year'=>2010,'replace_year'=>2013,'replace_cost'=>2500.00,'replace_date'=>'2014-01-01','description'=>'Model: -[6218MC1]-

	Serial: KQFKLH8','replaced'=>1),
	  array('id'=>17,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS11','user'=>'Sarah','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2009-12-22','purchase_year'=>2009,'replace_year'=>2015,'replace_cost'=>2500.00,'replace_date'=>'2015-12-22','description'=>'Model: 7268C4U

	Serial: MJGB535','replaced'=>0),
	  array('id'=>18,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS12','user'=>'Tracey','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2009-12-22','purchase_year'=>2009,'replace_year'=>2013,'replace_cost'=>2500.00,'replace_date'=>'2013-12-22','description'=>'Model: 7268C4U

	Serial: MJGB549','replaced'=>0),
	  array('id'=>19,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS13','user'=>'Trenton','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2009-12-22','purchase_year'=>2009,'replace_year'=>2013,'replace_cost'=>2500.00,'replace_date'=>'2013-12-22','description'=>'Model: 7268C4U

	Serial: MJGB550','replaced'=>0),
	  array('id'=>20,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS14','user'=>'Tina','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2009-12-22','purchase_year'=>2009,'replace_year'=>2013,'replace_cost'=>2500.00,'replace_date'=>'2013-12-22','description'=>'Model: 7268C4U

	Serial: MJGB531','replaced'=>0),
	  array('id'=>21,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS15','user'=>'Ken','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2010-12-22','purchase_year'=>2010,'replace_year'=>2013,'replace_cost'=>2500.00,'replace_date'=>'2013-12-22','description'=>'Model: 7268C4U

	Serial: MJGB552','replaced'=>0),
	  array('id'=>22,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS17','user'=>'Nicole','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2009-12-22','purchase_year'=>2009,'replace_year'=>2012,'replace_cost'=>2500.00,'replace_date'=>'2012-12-22','description'=>'Model: 7268C4U

	Serial: MJGB530','replaced'=>0),
	  array('id'=>23,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS18','user'=>'Brenda','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2008-06-22','purchase_year'=>2008,'replace_year'=>2012,'replace_cost'=>2500.00,'replace_date'=>'2012-06-22','description'=>'Model: 7268C4U

	Serial: MJGB532','replaced'=>1),
	  array('id'=>24,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS19','user'=>'Lina','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2009-12-22','purchase_year'=>2009,'replace_year'=>2012,'replace_cost'=>2500.00,'replace_date'=>'2012-12-22','description'=>'Model: 7268C4U

	Serial: MJGB533','replaced'=>1),
	  array('id'=>25,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS20','user'=>'Itadmin','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'1970-01-01','purchase_year'=>2010,'replace_year'=>2013,'replace_cost'=>2500.00,'replace_date'=>'2014-01-01','description'=>'Model: INVALID

	Serial: INVALID','replaced'=>0),
	  array('id'=>26,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS21','user'=>'Itsupport','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'1970-01-01','purchase_year'=>2010,'replace_year'=>2013,'replace_cost'=>2500.00,'replace_date'=>'2014-01-01','description'=>'Model: 4524AB9

	Serial: MJDWKBG','replaced'=>0),
	  array('id'=>27,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS22','user'=>'Jan','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'1970-01-01','purchase_year'=>2010,'replace_year'=>2014,'replace_cost'=>2500.00,'replace_date'=>'2014-01-01','description'=>'Model: 4157CTO

	Serial: MJFTERY','replaced'=>0),
	  array('id'=>28,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS23','user'=>'Manny','manufacturer'=>'IBM','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2006-04-30','purchase_year'=>2006,'replace_year'=>2015,'replace_cost'=>2500.00,'replace_date'=>'2015-04-30','description'=>'Model: 811343U

	Serial: LKZKL7K','replaced'=>0),
	  array('id'=>30,'asset_type_id'=>3,'name'=>'FNTCONOTTVH1','user'=>'Administrator','manufacturer'=>'IBM','quantity'=>1,'purchase_cost'=>6500.00,'invoice_date'=>'2011-01-01','purchase_year'=>2010,'replace_year'=>2015,'replace_cost'=>6500.00,'replace_date'=>'2015-04-01','description'=>'Model: IBM eServer x3500-[7977AC1]-

	Serial: KQARGX8','replaced'=>0),
	  array('id'=>31,'asset_type_id'=>2,'name'=>'FNTCONOTTWKS01','user'=>'Brent','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2011-03-07','purchase_year'=>2010,'replace_year'=>2014,'replace_cost'=>2500.00,'replace_date'=>'2014-04-07','description'=>'Model: 0806B3U

	Serial: MJLEWA7','replaced'=>0),
	  array('id'=>32,'asset_type_id'=>2,'name'=>'FNTCONOTTWKS02','user'=>'Arthur','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2011-03-07','purchase_year'=>2010,'replace_year'=>2014,'replace_cost'=>2500.00,'replace_date'=>'2014-04-07','description'=>'Model: 0806B3U

	Serial: MJLEWA3','replaced'=>0),
	  array('id'=>33,'asset_type_id'=>2,'name'=>'FNTCONOTTWKS03','user'=>'Robert','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2011-03-07','purchase_year'=>2010,'replace_year'=>2014,'replace_cost'=>2500.00,'replace_date'=>'2014-04-07','description'=>'Model: 0806B3U

	Serial: MJMBBP2','replaced'=>0),
	  array('id'=>34,'asset_type_id'=>2,'name'=>'FNTCONOTTWKS04','user'=>'Agnes','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2011-03-07','purchase_year'=>2010,'replace_year'=>2014,'replace_cost'=>2500.00,'replace_date'=>'2014-04-07','description'=>'Model: 0806B3U

	Serial: MJMBBP4','replaced'=>0),
	  array('id'=>35,'asset_type_id'=>2,'name'=>'FNTCONOTTWKS05','user'=>'Lilian','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2011-03-07','purchase_year'=>2010,'replace_year'=>2014,'replace_cost'=>2500.00,'replace_date'=>'2014-04-07','description'=>'Model: 0806B3U

	Serial: MJMBBP1','replaced'=>0),
	  array('id'=>36,'asset_type_id'=>2,'name'=>'FNTCONOTTWKS12','user'=>'Resource','manufacturer'=>'IBM','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2011-01-01','purchase_year'=>2010,'replace_year'=>2014,'replace_cost'=>2500.00,'replace_date'=>'2014-04-01','description'=>'Model: 8189KUM

	Serial: KCWA38D','replaced'=>0),
	  array('id'=>37,'asset_type_id'=>5,'name'=>'Adobe Acrobat Pro 10.0','user'=>'Lillian','manufacturer'=>'Adobe','quantity'=>1,'purchase_cost'=>1100.00,'invoice_date'=>'1970-01-01','purchase_year'=>2010,'replace_year'=>0,'replace_cost'=>0.00,'replace_date'=>'2013-01-01','description'=>'','replaced'=>0),
	  array('id'=>38,'asset_type_id'=>5,'name'=>'Windows Remote Desktop Services - User CAL','user'=>'','manufacturer'=>'Microsoft','quantity'=>15,'purchase_cost'=>2250.00,'invoice_date'=>'2010-04-28','purchase_year'=>2010,'replace_year'=>2012,'replace_cost'=>2250.00,'replace_date'=>'2012-04-30','description'=>'','replaced'=>0),
	  array('id'=>39,'asset_type_id'=>5,'name'=>'Windows Remote Desktop Services - User CAL','user'=>'','manufacturer'=>'Microsoft','quantity'=>10,'purchase_cost'=>1500.00,'invoice_date'=>'1970-01-01','purchase_year'=>2011,'replace_year'=>2013,'replace_cost'=>1500.00,'replace_date'=>'2013-05-31','description'=>'','replaced'=>0),
	  array('id'=>40,'asset_type_id'=>5,'name'=>'Office Standard 2013','user'=>'','manufacturer'=>'Microsoft','quantity'=>10,'purchase_cost'=>6000.00,'invoice_date'=>'1970-01-01','purchase_year'=>2010,'replace_year'=>2013,'replace_cost'=>6000.00,'replace_date'=>'2013-08-31','description'=>'','replaced'=>0),
	  array('id'=>41,'asset_type_id'=>5,'name'=>'Office Pro 2010 ','user'=>'','manufacturer'=>'Microsoft','quantity'=>1,'purchase_cost'=>850.00,'invoice_date'=>'1970-01-01','purchase_year'=>2010,'replace_year'=>2014,'replace_cost'=>850.00,'replace_date'=>'2014-04-01','description'=>'','replaced'=>0),
	  array('id'=>42,'asset_type_id'=>5,'name'=>'Office Professional Plus 2010','user'=>'Tracey','manufacturer'=>'Microsoft','quantity'=>1,'purchase_cost'=>1250.00,'invoice_date'=>'2011-03-01','purchase_year'=>2010,'replace_year'=>2014,'replace_cost'=>1250.00,'replace_date'=>'2014-04-01','description'=>'','replaced'=>0),
	  array('id'=>43,'asset_type_id'=>5,'name'=>'Office Standard 2010 SA','user'=>'','manufacturer'=>'Microsoft','quantity'=>11,'purchase_cost'=>9350.00,'invoice_date'=>'2011-03-01','purchase_year'=>2010,'replace_year'=>2014,'replace_cost'=>9350.00,'replace_date'=>'2014-04-01','description'=>'','replaced'=>0),
	  array('id'=>44,'asset_type_id'=>5,'name'=>'Office SharePoint Server','user'=>2013,'manufacturer'=>'Microsoft','quantity'=>1,'purchase_cost'=>10750.00,'invoice_date'=>'1970-01-01','purchase_year'=>2010,'replace_year'=>2013,'replace_cost'=>10750.00,'replace_date'=>'2013-04-01','description'=>'','replaced'=>0),
	  array('id'=>45,'asset_type_id'=>5,'name'=>'Office SharePoint Server Enterprise CAL','user'=>'','manufacturer'=>'Microsoft','quantity'=>30,'purchase_cost'=>6000.00,'invoice_date'=>'1970-01-01','purchase_year'=>2010,'replace_year'=>2013,'replace_cost'=>6000.00,'replace_date'=>'2013-04-01','description'=>'','replaced'=>0),
	  array('id'=>46,'asset_type_id'=>5,'name'=>'Windows Server 2008 R2','user'=>'','manufacturer'=>'Microsoft','quantity'=>1,'purchase_cost'=>1650.00,'invoice_date'=>'1970-01-01','purchase_year'=>2010,'replace_year'=>2013,'replace_cost'=>1650.00,'replace_date'=>'2013-03-31','description'=>'','replaced'=>0),
	  array('id'=>47,'asset_type_id'=>5,'name'=>'Windows SBS Standard 2011 SA','user'=>'','manufacturer'=>'Microsoft','quantity'=>1,'purchase_cost'=>2250.00,'invoice_date'=>'2011-03-01','purchase_year'=>2010,'replace_year'=>2012,'replace_cost'=>2250.00,'replace_date'=>'2013-03-31','description'=>'','replaced'=>0),
	  array('id'=>48,'asset_type_id'=>5,'name'=>'Windows SBS Standard 2011 CAL SA','user'=>'','manufacturer'=>'Microsoft','quantity'=>5,'purchase_cost'=>675.00,'invoice_date'=>'2011-03-01','purchase_year'=>2010,'replace_year'=>2012,'replace_cost'=>675.00,'replace_date'=>'2013-03-31','description'=>'','replaced'=>0),
	  array('id'=>49,'asset_type_id'=>5,'name'=>'Windows SBS Premium Addon 2011','user'=>'','manufacturer'=>'Microsoft','quantity'=>1,'purchase_cost'=>3150.00,'invoice_date'=>'1970-01-01','purchase_year'=>2010,'replace_year'=>0,'replace_cost'=>0.00,'replace_date'=>'2014-04-01','description'=>'','replaced'=>0),
	  array('id'=>50,'asset_type_id'=>5,'name'=>'Windows SBS Premium Addon 2011 CAL ','user'=>'','manufacturer'=>'Microsoft','quantity'=>10,'purchase_cost'=>1700.00,'invoice_date'=>'1970-01-01','purchase_year'=>2011,'replace_year'=>0,'replace_cost'=>0.00,'replace_date'=>'2014-03-01','description'=>'','replaced'=>0),
	  array('id'=>51,'asset_type_id'=>5,'name'=>'Windows SBS 2011 CAL SA','user'=>'','manufacturer'=>'Microsoft','quantity'=>0,'purchase_cost'=>1400.00,'invoice_date'=>'2011-03-31','purchase_year'=>2010,'replace_year'=>2012,'replace_cost'=>1400.00,'replace_date'=>'2013-03-31','description'=>'','replaced'=>0),
	  array('id'=>52,'asset_type_id'=>5,'name'=>'NOD32 Antivirus','user'=>'','manufacturer'=>'ESET','quantity'=>60,'purchase_cost'=>1080.00,'invoice_date'=>'1970-01-01','purchase_year'=>2014,'replace_year'=>2015,'replace_cost'=>1080.00,'replace_date'=>'2013-03-05','description'=>'','replaced'=>0),
	  array('id'=>53,'asset_type_id'=>7,'name'=>'Barracuda 210 Spam Filter','user'=>'','manufacturer'=>'Barracuda','quantity'=>0,'purchase_cost'=>1600.00,'invoice_date'=>'2010-07-15','purchase_year'=>2010,'replace_year'=>2011,'replace_cost'=>1600.00,'replace_date'=>'2011-07-30','description'=>'','replaced'=>0),
	  array('id'=>56,'asset_type_id'=>5,'name'=>'NOD32 Antivirus','user'=>'','manufacturer'=>'ESET','quantity'=>60,'purchase_cost'=>1080.00,'invoice_date'=>'2013-03-05','purchase_year'=>2012,'replace_year'=>2013,'replace_cost'=>1080.00,'replace_date'=>'2014-03-05','description'=>'','replaced'=>0),
	  array('id'=>57,'asset_type_id'=>1,'name'=>'FNTCONOTTLAP02','user'=>'Brent','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>4400.00,'invoice_date'=>'1970-01-01','purchase_year'=>2009,'replace_year'=>2013,'replace_cost'=>2700.00,'replace_date'=>'2014-02-20','description'=>'27763CU

	S/N R8-YXTAD','replaced'=>1),
	  array('id'=>58,'asset_type_id'=>1,'name'=>'FNTCONOTTLAP03','user'=>'Robert','manufacturer'=>'DELL','quantity'=>0,'purchase_cost'=>2700.00,'invoice_date'=>'2009-03-30','purchase_year'=>2008,'replace_year'=>2011,'replace_cost'=>2700.00,'replace_date'=>'2012-03-30','description'=>'Latitude D510

	S/N OT7570','replaced'=>0),
	  array('id'=>59,'asset_type_id'=>5,'name'=>'BESR 2010','user'=>'','manufacturer'=>'Symantec','quantity'=>4,'purchase_cost'=>3000.00,'invoice_date'=>'2010-03-15','purchase_year'=>2009,'replace_year'=>2010,'replace_cost'=>3000.00,'replace_date'=>'2011-03-15','description'=>'','replaced'=>0),
	  array('id'=>60,'asset_type_id'=>5,'name'=>'BESR 2010','user'=>'','manufacturer'=>'Symantec','quantity'=>4,'purchase_cost'=>3000.00,'invoice_date'=>'1970-01-01','purchase_year'=>2012,'replace_year'=>2014,'replace_cost'=>5000.00,'replace_date'=>'2014-03-15','description'=>'','replaced'=>0),
	  array('id'=>61,'asset_type_id'=>8,'name'=>'CM4540 MFP','user'=>'Jan','manufacturer'=>'HP','quantity'=>0,'purchase_cost'=>4000.00,'invoice_date'=>'2012-04-27','purchase_year'=>2012,'replace_year'=>2015,'replace_cost'=>4000.00,'replace_date'=>'2015-04-27','description'=>'HP Color LaserJet CM4540 MFP

	192.168.200.138','replaced'=>0),
	  array('id'=>62,'asset_type_id'=>7,'name'=>'Messasge Archiver','user'=>'','manufacturer'=>'Barracuda','quantity'=>0,'purchase_cost'=>3000.00,'invoice_date'=>'2012-03-15','purchase_year'=>2011,'replace_year'=>2011,'replace_cost'=>3000.00,'replace_date'=>'2012-03-15','description'=>'Proposed','replaced'=>0),
	  array('id'=>63,'asset_type_id'=>2,'name'=>'Allienware','user'=>'Dave','manufacturer'=>'DELL','quantity'=>0,'purchase_cost'=>3000.00,'invoice_date'=>'2012-03-15','purchase_year'=>2011,'replace_year'=>2011,'replace_cost'=>3000.00,'replace_date'=>'2012-03-15','description'=>'Proposed','replaced'=>1),
	  array('id'=>64,'asset_type_id'=>8,'name'=>'Color Printer','user'=>'Ken','manufacturer'=>'HP','quantity'=>0,'purchase_cost'=>450.00,'invoice_date'=>'2012-03-15','purchase_year'=>2011,'replace_year'=>2011,'replace_cost'=>450.00,'replace_date'=>'2012-03-15','description'=>'Proposed','replaced'=>0),
	  array('id'=>65,'asset_type_id'=>1,'name'=>'FNTCBCKAMLAP14','user'=>'Webadmin','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>2700.00,'invoice_date'=>'2012-03-15','purchase_year'=>2011,'replace_year'=>2014,'replace_cost'=>2700.00,'replace_date'=>'2015-03-15','description'=>'','replaced'=>0),
	  array('id'=>66,'asset_type_id'=>5,'name'=>'NOD32 Antivirus','user'=>'','manufacturer'=>'ESET','quantity'=>60,'purchase_cost'=>1080.00,'invoice_date'=>'2014-03-05','purchase_year'=>2013,'replace_year'=>2014,'replace_cost'=>1080.00,'replace_date'=>'2015-03-05','description'=>'','replaced'=>0),
	  array('id'=>67,'asset_type_id'=>1,'name'=>'FNTCBCKAMLAP10','user'=>'Ken','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2700.00,'invoice_date'=>'2012-05-15','purchase_year'=>2012,'replace_year'=>2015,'replace_cost'=>2700.00,'replace_date'=>'2015-05-15','description'=>'Lenovo X1

	Model: 1286-CTO

	Serial: R9NR0AC','replaced'=>0),
	  array('id'=>68,'asset_type_id'=>1,'name'=>'FNTCBCKAMLAP06','user'=>'infomanager','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>2700.00,'invoice_date'=>'2012-05-15','purchase_year'=>2012,'replace_year'=>2015,'replace_cost'=>2700.00,'replace_date'=>'2015-05-15','description'=>'Lenovo X1

	Model: 1286CTO

	SN: R9-NR0AB','replaced'=>0),
	  array('id'=>69,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS04','user'=>'Marlene','manufacturer'=>'IBM','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2012-06-04','purchase_year'=>2012,'replace_year'=>2015,'replace_cost'=>2500.00,'replace_date'=>'2015-06-04','description'=>'Model:4166CTO

	Serial: MJLVMWR','replaced'=>0),
	  array('id'=>70,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS24','user'=>'Brenda','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2012-07-18','purchase_year'=>2012,'replace_year'=>2015,'replace_cost'=>2500.00,'replace_date'=>'2015-07-18','description'=>'Model: 7268C4U

	Serial: MJGB532','replaced'=>0),
	  array('id'=>71,'asset_type_id'=>1,'name'=>'FNTCBCKAMLAP11','user'=>'Trenton','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2700.00,'invoice_date'=>'2012-07-18','purchase_year'=>2012,'replace_year'=>2015,'replace_cost'=>2700.00,'replace_date'=>'2015-07-18','description'=>'Model: 64781HU

	Serial: L3ABT1F','replaced'=>0),
	  array('id'=>72,'asset_type_id'=>2,'name'=>'Allienware','user'=>'Dave','manufacturer'=>'DELL','quantity'=>0,'purchase_cost'=>3000.00,'invoice_date'=>'2012-07-18','purchase_year'=>2012,'replace_year'=>2015,'replace_cost'=>3000.00,'replace_date'=>'2015-07-18','description'=>'Proposed','replaced'=>0),
	  array('id'=>73,'asset_type_id'=>1,'name'=>'FNTCBCKAMLAP15','user'=>'Ken Scopic','manufacturer'=>'Samsung','quantity'=>0,'purchase_cost'=>2500.00,'invoice_date'=>'2012-08-01','purchase_year'=>2012,'replace_year'=>2015,'replace_cost'=>2500.00,'replace_date'=>'2015-08-17','description'=>'Series 9
	Model: NP900X3c
	S/N: HR4J91GC500393T','replaced'=>0),
	  array('id'=>74,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS19','user'=>'Lina','manufacturer'=>'Lenovo','quantity'=>1,'purchase_cost'=>2500.00,'invoice_date'=>'2012-10-24','purchase_year'=>2012,'replace_year'=>2015,'replace_cost'=>2500.00,'replace_date'=>'2015-10-24','description'=>'Model: 4166CT0

	Serial: MJLVMWT','replaced'=>0),
	  array('id'=>75,'asset_type_id'=>1,'name'=>'FNTCCOM01','user'=>'Ken Marsh','manufacturer'=>'Samsung','quantity'=>0,'purchase_cost'=>2000.00,'invoice_date'=>'2012-11-23','purchase_year'=>2012,'replace_year'=>2015,'replace_cost'=>2000.00,'replace_date'=>'2015-11-23','description'=>'Samsung Series 9
	P/N NP900X3C-A02CA
	S/N HWU091GCA00355L','replaced'=>0),
	  array('id'=>76,'asset_type_id'=>1,'name'=>'FNTCCOM02','user'=>'Lester Lafond','manufacturer'=>'Samsung ','quantity'=>0,'purchase_cost'=>2000.00,'invoice_date'=>'2012-11-23','purchase_year'=>2012,'replace_year'=>2014,'replace_cost'=>2000.00,'replace_date'=>'2014-11-23','description'=>'Samsung Series 9
	P/N NP900X3C-A01CA
	S/N HR4J91GC500510M','replaced'=>0),
	  array('id'=>77,'asset_type_id'=>1,'name'=>'FNTCCOM03','user'=>'Terry Nicholas','manufacturer'=>'Samsung','quantity'=>0,'purchase_cost'=>2000.00,'invoice_date'=>'2012-11-23','purchase_year'=>2012,'replace_year'=>2014,'replace_cost'=>2000.00,'replace_date'=>'2014-11-23','description'=>'Samsung Series 9
	P/N NP900X3C-A02CA
	S/N HR4J91GC500599T','replaced'=>0),
	  array('id'=>78,'asset_type_id'=>9,'name'=>'FNTCCOM04','user'=>'Randy Price','manufacturer'=>'Microsoft','quantity'=>0,'purchase_cost'=>1600.00,'invoice_date'=>'1970-01-01','purchase_year'=>2012,'replace_year'=>2015,'replace_cost'=>1600.00,'replace_date'=>'2012-11-23','description'=>'Surface RT
	S/N 036326124452','replaced'=>0),
	  array('id'=>79,'asset_type_id'=>9,'name'=>'FNTCCOM05','user'=>'Leslie Brochu','manufacturer'=>'Microsoft','quantity'=>0,'purchase_cost'=>1600.00,'invoice_date'=>'1970-01-01','purchase_year'=>2012,'replace_year'=>2015,'replace_cost'=>1600.00,'replace_date'=>'2012-11-23','description'=>'Surface RT 
	S/N 018839624452','replaced'=>0),
	  array('id'=>80,'asset_type_id'=>9,'name'=>'FNTCCOM06','user'=>'Celine Auclair','manufacturer'=>'Microsoft','quantity'=>0,'purchase_cost'=>1600.00,'invoice_date'=>'1970-01-01','purchase_year'=>2012,'replace_year'=>2015,'replace_cost'=>1600.00,'replace_date'=>'2012-11-23','description'=>'Surface RT
	S/N 196657124252','replaced'=>0),
	  array('id'=>81,'asset_type_id'=>9,'name'=>'FNTCCOM07','user'=>'Ann Shaw','manufacturer'=>'Microsoft','quantity'=>0,'purchase_cost'=>1600.00,'invoice_date'=>'1970-01-01','purchase_year'=>2012,'replace_year'=>2015,'replace_cost'=>1600.00,'replace_date'=>'2012-12-13','description'=>'Surface RT
	S/N 005912424452
	','replaced'=>0),
	  array('id'=>82,'asset_type_id'=>9,'name'=>'FNTCCOM08','user'=>'Bill McCue','manufacturer'=>'Microsoft','quantity'=>0,'purchase_cost'=>1600.00,'invoice_date'=>'1970-01-01','purchase_year'=>2012,'replace_year'=>2015,'replace_cost'=>1600.00,'replace_date'=>'2012-12-13','description'=>'Surface RT
	S/N 016066224452','replaced'=>0),
	  array('id'=>83,'asset_type_id'=>9,'name'=>'FNTCBCKAMTAB01','user'=>'FNTC Staff 01','manufacturer'=>'Microsoft','quantity'=>0,'purchase_cost'=>1800.00,'invoice_date'=>'1970-01-01','purchase_year'=>2012,'replace_year'=>2013,'replace_cost'=>1800.00,'replace_date'=>'2013-03-08','description'=>'','replaced'=>0),
	  array('id'=>84,'asset_type_id'=>9,'name'=>'FNTCBCKAMTAB02','user'=>'FNTC Staff 02','manufacturer'=>'Microsoft','quantity'=>0,'purchase_cost'=>1800.00,'invoice_date'=>'1970-01-01','purchase_year'=>2012,'replace_year'=>2013,'replace_cost'=>1800.00,'replace_date'=>'2013-03-08','description'=>'','replaced'=>0),
	  array('id'=>85,'asset_type_id'=>9,'name'=>'FNTCBCKAMTAB03','user'=>'FNTC Staff 03','manufacturer'=>'Microsoft','quantity'=>0,'purchase_cost'=>1800.00,'invoice_date'=>'1970-01-01','purchase_year'=>2012,'replace_year'=>2013,'replace_cost'=>1800.00,'replace_date'=>'2013-03-08','description'=>'','replaced'=>0),
	  array('id'=>86,'asset_type_id'=>6,'name'=>'Setup/Config','user'=>'FNTC Organization','manufacturer'=>'Smart Group','quantity'=>0,'purchase_cost'=>8000.00,'invoice_date'=>'1970-01-01','purchase_year'=>2012,'replace_year'=>2013,'replace_cost'=>8000.00,'replace_date'=>'2013-03-08','description'=>'Configuration and setup for all hardware components for fiscal 2013','replaced'=>0),
	  array('id'=>87,'asset_type_id'=>1,'name'=>'FNTCBCKAMLAP04','user'=>'Jan','manufacturer'=>'Samsung','quantity'=>0,'purchase_cost'=>2700.00,'invoice_date'=>'1970-01-01','purchase_year'=>2012,'replace_year'=>2013,'replace_cost'=>2700.00,'replace_date'=>'2013-05-08','description'=>'','replaced'=>0),
	  array('id'=>88,'asset_type_id'=>1,'name'=>'FNTCONOTTLAP02','user'=>'Brent','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>2700.00,'invoice_date'=>'2013-03-08','purchase_year'=>2012,'replace_year'=>2012,'replace_cost'=>2700.00,'replace_date'=>'2013-03-08','description'=>'','replaced'=>0),
	  array('id'=>89,'asset_type_id'=>10,'name'=>'FNTCBCKAMPDA01','user'=>'Rick','manufacturer'=>'Samsung','quantity'=>0,'purchase_cost'=>850.00,'invoice_date'=>'1970-01-01','purchase_year'=>2012,'replace_year'=>2013,'replace_cost'=>850.00,'replace_date'=>'2013-03-08','description'=>'','replaced'=>0),
	  array('id'=>90,'asset_type_id'=>10,'name'=>'FNTCBCKAMPDA02','user'=>'Trenton','manufacturer'=>'Samsung','quantity'=>0,'purchase_cost'=>850.00,'invoice_date'=>'1970-01-01','purchase_year'=>2012,'replace_year'=>2013,'replace_cost'=>850.00,'replace_date'=>'2013-03-08','description'=>'','replaced'=>0),
	  array('id'=>91,'asset_type_id'=>10,'name'=>'FNTCBCKAMPDA03','user'=>'Brent','manufacturer'=>'Samsung','quantity'=>0,'purchase_cost'=>850.00,'invoice_date'=>'1970-01-01','purchase_year'=>2012,'replace_year'=>2013,'replace_cost'=>850.00,'replace_date'=>'2013-03-08','description'=>'','replaced'=>0),
	  array('id'=>92,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS30','user'=>'Ken (Home  Office)','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>2500.00,'invoice_date'=>'1970-01-01','purchase_year'=>2010,'replace_year'=>2013,'replace_cost'=>2500.00,'replace_date'=>'0000-00-00','description'=>'','replaced'=>0),
	  array('id'=>93,'asset_type_id'=>7,'name'=>'FNTCBCKAMVCF03','user'=>'Video Conf ','manufacturer'=>'Polycom','quantity'=>0,'purchase_cost'=>14800.00,'invoice_date'=>'1970-01-01','purchase_year'=>2009,'replace_year'=>2013,'replace_cost'=>14800.00,'replace_date'=>'0000-00-00','description'=>'Polycom HDX8000 w/ 4 Site MultiPoint option and 1Year Rep Warranty','replaced'=>0),
	  array('id'=>94,'asset_type_id'=>7,'name'=>'FNTCBCKAMFW1','user'=>'Fire Wall','manufacturer'=>'DELL','quantity'=>0,'purchase_cost'=>5000.00,'invoice_date'=>'1970-01-01','purchase_year'=>2012,'replace_year'=>2013,'replace_cost'=>5000.00,'replace_date'=>'0000-00-00','description'=>'SonicWall SRA 4600

	C0EAE42CC024','replaced'=>0),
	  array('id'=>95,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS17','user'=>'Nicole','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>2500.00,'invoice_date'=>'1970-01-01','purchase_year'=>2012,'replace_year'=>2015,'replace_cost'=>2500.00,'replace_date'=>'0000-00-00','description'=>'Model: 4166CTO
	Serial: MJLVMWP','replaced'=>0),
	  array('id'=>96,'asset_type_id'=>1,'name'=>'FNTCONOTTLAP03','user'=>'Robert','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>2700.00,'invoice_date'=>'1970-01-01','purchase_year'=>2012,'replace_year'=>2016,'replace_cost'=>27.00,'replace_date'=>'0000-00-00','description'=>'Thinkpad 
	Product # 1286CTO
	SN # R9-NR0A9','replaced'=>0),
	  array('id'=>97,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS04','user'=>'Marlene','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>2500.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2016,'replace_cost'=>2500.00,'replace_date'=>'0000-00-00','description'=>'Model: 2697CTO
	Serial: MJ239VE','replaced'=>0),
	  array('id'=>98,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS12','user'=>'Tracey','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>2500.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2016,'replace_cost'=>2500.00,'replace_date'=>'0000-00-00','description'=>'Model: 2697CTO
	Serial: MJ239VF','replaced'=>0),
	  array('id'=>99,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS13','user'=>'Trenton','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>2500.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2016,'replace_cost'=>2500.00,'replace_date'=>'0000-00-00','description'=>'Model: 2697CTO
	Serial: MJ239VG','replaced'=>0),
	  array('id'=>100,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS14','user'=>'TBD','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>2500.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2016,'replace_cost'=>2500.00,'replace_date'=>'0000-00-00','description'=>'Model: 2697CTO
	Serial: MJ239VH','replaced'=>0),
	  array('id'=>101,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS15','user'=>'Ken','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>2500.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2016,'replace_cost'=>2500.00,'replace_date'=>'0000-00-00','description'=>'Model: 2697CTO
	Serial: MJ239VK','replaced'=>0),
	  array('id'=>102,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS30','user'=>'Ken (Home Office)','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>2500.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2016,'replace_cost'=>2500.00,'replace_date'=>'0000-00-00','description'=>'Model: 2697CTO
	Serial: MJ241AM','replaced'=>0),
	  array('id'=>103,'asset_type_id'=>7,'name'=>'FNTCBCKAMFW1','user'=>'Fire Wall','manufacturer'=>'SonicWall','quantity'=>0,'purchase_cost'=>5000.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2017,'replace_cost'=>5000.00,'replace_date'=>'0000-00-00','description'=>'SonicWall SRA 4600

	C0EAE42CC024','replaced'=>0),
	  array('id'=>104,'asset_type_id'=>7,'name'=>'FNTCBCKAMVCF03','user'=>'Video Conf ','manufacturer'=>'Polycom','quantity'=>0,'purchase_cost'=>14800.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2017,'replace_cost'=>15000.00,'replace_date'=>'0000-00-00','description'=>'Polycom HDX8000 w/ 4 Site MultiPoint option and 1Year Rep Warranty
	Serial: 1007EC','replaced'=>0),
	  array('id'=>105,'asset_type_id'=>10,'name'=>'FNTCBCKAMPDA01','user'=>'Rick','manufacturer'=>'Apple','quantity'=>0,'purchase_cost'=>850.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2015,'replace_cost'=>850.00,'replace_date'=>'0000-00-00','description'=>'iPhone 5 16GB
	(250) 320-2084
	IMEI: 013427005437231','replaced'=>0),
	  array('id'=>106,'asset_type_id'=>10,'name'=>'FNTCBCKAMPDA02','user'=>'Trenton','manufacturer'=>'Apple','quantity'=>0,'purchase_cost'=>850.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2015,'replace_cost'=>850.00,'replace_date'=>'0000-00-00','description'=>'iPhone 5 16GB
	(250) 371-1368
	IMEI: 013427005181466','replaced'=>0),
	  array('id'=>107,'asset_type_id'=>10,'name'=>'FNTCBCKAMPDA03','user'=>'Brent','manufacturer'=>'Apple','quantity'=>0,'purchase_cost'=>850.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2015,'replace_cost'=>850.00,'replace_date'=>'0000-00-00','description'=>'iPhone 5 16GB
	(613) 761-0698
	IMEI: 013431000317380','replaced'=>0),
	  array('id'=>108,'asset_type_id'=>1,'name'=>'FNTCBCKAMLAP04','user'=>'Jan','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>2700.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2016,'replace_cost'=>2700.00,'replace_date'=>'0000-00-00','description'=>'Thinkpad X1','replaced'=>0),
	  array('id'=>109,'asset_type_id'=>1,'name'=>'FNTCBCKAMLAP12','user'=>'Sarah','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>2700.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2016,'replace_cost'=>2700.00,'replace_date'=>'0000-00-00','description'=>'ThinkPad T430s
	Model: 2352-CTO
	Serial: R9Y805E','replaced'=>0),
	  array('id'=>110,'asset_type_id'=>1,'name'=>'FNTCONOTTLAP02','user'=>'Brent','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>2700.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2016,'replace_cost'=>2700.00,'replace_date'=>'0000-00-00','description'=>'ThinkPad T430s
	Model: 2352-CTO
	Serial: R9Y805F','replaced'=>0),
	  array('id'=>111,'asset_type_id'=>1,'name'=>'FNTCBCKAMLAP01','user'=>'Nicole','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>2700.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2016,'replace_cost'=>2700.00,'replace_date'=>'0000-00-00','description'=>'ThinkPad T430s
	Model: 2352-CTO
	Serial: R9Y805G','replaced'=>0),
	  array('id'=>112,'asset_type_id'=>1,'name'=>'FNTCBCKAMLAP13','user'=>'Rick','manufacturer'=>'Lenovo','quantity'=>0,'purchase_cost'=>2700.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2016,'replace_cost'=>2700.00,'replace_date'=>'0000-00-00','description'=>'ThinkPad X1','replaced'=>0),
	  array('id'=>113,'asset_type_id'=>9,'name'=>'FNTCBCKAMTAB01','user'=>'FNTC Staff 01','manufacturer'=>'Microsoft','quantity'=>0,'purchase_cost'=>1800.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2015,'replace_cost'=>1800.00,'replace_date'=>'0000-00-00','description'=>'Surface Pro 64GB
	Model: 1514
	Serial: 006933730353','replaced'=>0),
	  array('id'=>114,'asset_type_id'=>9,'name'=>'FNTCBCKAMTAB02','user'=>'FNTC Staff 02','manufacturer'=>'Microsoft','quantity'=>0,'purchase_cost'=>1800.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2015,'replace_cost'=>1800.00,'replace_date'=>'0000-00-00','description'=>'Surface Pro 64GB
	Model: 1514
	Serial: 008554630353','replaced'=>0),
	  array('id'=>115,'asset_type_id'=>9,'name'=>'FNTCBCKAMTAB03','user'=>'FNTC Staff 03','manufacturer'=>'Microsoft','quantity'=>0,'purchase_cost'=>1800.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2015,'replace_cost'=>1800.00,'replace_date'=>'0000-00-00','description'=>'Surface Pro 64GB
	Model: 1514
	Serial: 015469330453','replaced'=>0),
	  array('id'=>116,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS20','user'=>'itadmin','manufacturer'=>'Sony','quantity'=>0,'purchase_cost'=>2500.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2016,'replace_cost'=>2500.00,'replace_date'=>'0000-00-00','description'=>'Viao SVL241A11L','replaced'=>0),
	  array('id'=>117,'asset_type_id'=>2,'name'=>'FNTCBCKAMWKS21','user'=>'Itsupport','manufacturer'=>'Sony','quantity'=>0,'purchase_cost'=>2500.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2016,'replace_cost'=>2500.00,'replace_date'=>'0000-00-00','description'=>'Viao SVL241A11L','replaced'=>0),
	  array('id'=>118,'asset_type_id'=>5,'name'=>'Office SharePoint Server','user'=>2013,'manufacturer'=>'Microsoft','quantity'=>1,'purchase_cost'=>10750.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2016,'replace_cost'=>10750.00,'replace_date'=>'0000-00-00','description'=>'Sharepoint 2013 standard
	JWGTJ-NM472-KC748-WJW7Q-H2XBX','replaced'=>0),
	  array('id'=>119,'asset_type_id'=>5,'name'=>'Office SharePoint Server Enterprise CAL','user'=>'','manufacturer'=>'Microsoft','quantity'=>30,'purchase_cost'=>6000.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2016,'replace_cost'=>6000.00,'replace_date'=>'0000-00-00','description'=>'','replaced'=>0),
	  array('id'=>120,'asset_type_id'=>5,'name'=>'Windows Remote Desktop Services - User CAL','user'=>'','manufacturer'=>'Microsoft','quantity'=>10,'purchase_cost'=>1500.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2015,'replace_cost'=>1500.00,'replace_date'=>'0000-00-00','description'=>'','replaced'=>0),
	  array('id'=>121,'asset_type_id'=>5,'name'=>'Office Standard 2013','user'=>'','manufacturer'=>'Microsoft','quantity'=>10,'purchase_cost'=>6000.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2016,'replace_cost'=>6000.00,'replace_date'=>'0000-00-00','description'=>'','replaced'=>0),
	  array('id'=>122,'asset_type_id'=>5,'name'=>'Windows Server 2012','user'=>'','manufacturer'=>'Microsoft','quantity'=>1,'purchase_cost'=>1650.00,'invoice_date'=>'1970-01-01','purchase_year'=>2013,'replace_year'=>2016,'replace_cost'=>1650.00,'replace_date'=>'0000-00-00','description'=>'Replaced Windows Server 2008 R2','replaced'=>0)
	);

	foreach($assets as $asset_arr) {
		$role = Role::where('name', $asset_arr['name'])->first();
		if (!$role) {
			$location = str_contains($asset_arr['name'], 'OTT') ? 'Ottawa' : 'Kamloops';
			$role = Role::create(array(
				'name' => $asset_arr['name'],
				'user' => $asset_arr['user'],
				'location' => $location
			));
		}
		$asset = new Asset(array(
			'asset_type_id' => $asset_arr['asset_type_id'],
			'ownership' => (($asset_arr['asset_type_id']==4) ? Asset::LICENSE : Asset::PURCHASE),
			'maker' => $asset_arr['manufacturer'],
			'product' => $asset_arr['name'],
			'purchase_cost' => $asset_arr['purchase_cost'],
			'replace_cost' => $asset_arr['replace_cost'],
			'purchase_year' => $asset_arr['purchase_year'],
			'lifespan' => ($asset_arr['replace_year'] - $asset_arr['purchase_year']),
			'license' => $asset_arr['description']
		));

		$role->assets()->save($asset);
	}

});*/