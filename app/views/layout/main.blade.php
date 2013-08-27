<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>{{ ThisOr::that('Smart - '.$title, 'Squall') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Squall | Asset Management and Budgeting">
    <meta name="author" content="Nolan Neustaeter">

    <link href="/packages/bootstrap/css/flatly.bootstrap.min.css" rel="stylesheet">
    <link href="/packages/bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
    <link href="/packages/select2/select2.css" rel="stylesheet">

    <style type="text/css">
      body {
        padding-top: 50px;
        padding-bottom: 20px;
      }
      .page-header {
        margin: 5px 0 0 0;
      }

      @media (max-width: 980px) {
        body {
          padding-top: 0px;
        }
        /* Enable use of floated navbar text */
        .navbar-text.pull-right {
          float: none;
          padding-left: 5px;
          padding-right: 5px;
        }
      }
    </style>
    @section('styles')
    @show
    

  </head>

  <body>

    <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container-fluid">
          <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>

          <a class="brand" href="#">Smart Asset Management</a>
          <div class="nav-collapse collapse">
            <p class="navbar-text pull-right">
              Logged in as <a href="#" class="navbar-link">Admin</a>
            </p>
            <ul class="nav">
              <li class="dropdown{{ starts_with(Request::path(), 'asset') ? ' active' : '' }}">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Assets <b class="caret"></b></a>
                <ul class="dropdown-menu">
                  <li><a href="{{ URL::to('assets') }}">All</a></li>
                  <li><a href="{{ URL::to('assets/new') }}">New</a></li>
                  <li class="divider"></li>
                  <li class="nav-header">By Type</li>
                  @foreach($types as $slug => $name)
                  <li><a href="{{ URL::to($slug) }}">{{ $name }}</a></li>
                  @endforeach
                </ul>
              </li>
              <li class="dropdown{{ starts_with(Request::path(), 'role') ? ' active' : '' }}">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Roles <b class="caret"></b></a>
                <ul class="dropdown-menu">
                  <li><a href="{{ URL::to('roles') }}">All</a></li>
                  <li><a href="{{ URL::to('roles/new') }}">New</a></li>
                  <li class="divider"></li>
                  <li class="nav-header">By User</li>
                  @foreach($role_users as $slug => $name)
                  <li><a href="{{ URL::to($slug) }}">{{ $name }}</a></li>
                  @endforeach
                </ul>
              </li>
              <li class="dropdown{{ starts_with(Request::path(), 'type') ? ' active' : '' }}">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Types <b class="caret"></b></a>
                <ul class="dropdown-menu">
                  <li><a href="{{ URL::to('types') }}">All</a></li>
                  <li><a href="{{ URL::to('types/new') }}">New</a></li>
                </ul>
              </li>
              <li class="dropdown{{ starts_with(Request::path(), 'report') ? ' active' : '' }}">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Reports <b class="caret"></b></a>
                <ul class="dropdown-menu">
                  <li class="dropdown-submenu">
                    <a tabindex="-1" href="{{ URL::to('reports/purchases/'.$fiscal_year) }}">Purchases by Year</a>
                    <ul class="dropdown-menu">
                      <li><a href="{{ URL::to('reports/purchases/'.($fiscal_year - 3)) }}">{{ ($fiscal_year - 3) }}</a></li>
                      <li><a href="{{ URL::to('reports/purchases/'.($fiscal_year - 2)) }}">{{ ($fiscal_year - 2) }}</a></li>
                      <li><a href="{{ URL::to('reports/purchases/'.($fiscal_year - 1)) }}">{{ ($fiscal_year - 1) }}</a></li>
                      <li class="divider"></li>
                      <li><a href="{{ URL::to('reports/purchases/'.$fiscal_year) }}">{{ $fiscal_year }}</a></li>
                      <li class="divider"></li>
                      <li><a href="{{ URL::to('reports/purchases/'.($fiscal_year + 1)) }}">{{ ($fiscal_year + 1) }}</a></li>
                    </ul>
                  </li>
                </ul>
              </li>
            </ul>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>

    <div class="container-fluid">
      
        @section('content')
        <div class="page-header clearfix">
					<h3 class="span7">
						@foreach($breadcrumbs as $slug => $name)
						<a href="{{ URL::to($slug) }}">{{ $name }}</a> / 
						@endforeach
						{{ $title }}
					</h3>
					@if(isset($alert))
					<div class="alert pull-right fade in alert-{{ $alert->type }}">
            @if($alert->dismiss)
					  <button type="button" class="close" data-dismiss="alert">&times;</button>
            @endif
					  <strong>{{ $alert->title }}</strong> {{ $alert->message }}
					</div>
					@endif
				</div>
        <h2 class="print-title">{{ $title }}</h2>
        @show
      

      <hr>

      <footer>
        <p>&copy; Smart Consulting Group 2013</p>
      </footer>

    </div><!--/.fluid-container-->

    <script src="/packages/jquery/jquery-2.0.2.min.js"></script>
    <script src="/packages/bootstrap/js/bootstrap.min.js"></script>
    <script src="/packages/datatables/jquery.dataTables.min.js"></script>
    <script src="/packages/select2/select2.min.js"></script>

    @if(isset($alert) && $alert->dismiss)
    <script>
	    $(document).ready(function() {
	    	setTimeout(function() {
	    		$('.alert').alert('close');
	    	}, 8000);
	    });
    </script>
    @endif

    @section('scripts')

    @show

  </body>
</html>
