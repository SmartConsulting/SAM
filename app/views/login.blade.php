<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>{{ ThisOr::that('Squall - '.$title, 'Squall') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Squall | Asset Management and Budgeting">
    <meta name="author" content="Nolan Neustaeter">

    <link href="/packages/bootstrap/css/flatly.bootstrap.min.css" rel="stylesheet">
    <link href="/packages/bootstrap/css/bootstrap-responsive.css" rel="stylesheet">

    <style>
      body {
        padding-top: 40px;
        padding-bottom: 40px;
        background-color: #f5f5f5;
      }

      .form-signin {
        max-width: 300px;
        padding: 19px 29px 29px;
        margin: 0 auto 20px;
        background-color: #fff;
        border: 1px solid #e5e5e5;
        -webkit-border-radius: 5px;
           -moz-border-radius: 5px;
                border-radius: 5px;
        -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
           -moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
                box-shadow: 0 1px 2px rgba(0,0,0,.05);
      }
      .form-signin .form-signin-heading,
      .form-signin .checkbox {
        margin-bottom: 10px;
      }
      .form-signin-heading, footer {
        text-align: center;
      }
      .form-signin input[type="text"],
      .form-signin input[type="password"] {
        font-size: 16px;
        height: auto;
        margin-bottom: 15px;
        padding: 7px 9px;
      }

    </style>
    @section('styles')
    @show
    

  </head>

  <body>

    <div class="container-fluid">
      
      <form class="form-signin" method="POST" action="{{ URL::to('login') }}">
        <h2 class="form-signin-heading">{{ $title }}</h2>

        @if(isset($alert))
        <div class="alert alert-block fade in alert-{{ $alert->type }}">
          @if($alert->dismiss)
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          @endif
          <h4>{{ $alert->title }}</h4>
          {{ $alert->message }}
        </div>
        @endif

        <input type="text" name="email" class="input-block-level" value="{{ Input::old('email') }}" placeholder="Email address">
        <input type="password" name="password" class="input-block-level" placeholder="Password">
        <label class="checkbox">
          <input type="checkbox" name="remember" value="checked" {{ Input::old('remember') }}> Remember me
        </label>
        <button class="btn btn-block btn-danger" type="submit">Sign in</button>
      </form>


      <footer>
        <p>&copy; Smart Consulting Group 2013</p>
      </footer>

    </div><!--/.fluid-container-->

    <script src="/packages/jquery/jquery-2.0.2.min.js"></script>
    <script src="/packages/bootstrap/js/bootstrap.min.js"></script>

    @if(isset($alert) && $alert->dismiss)
    <script>
	    $(document).ready(function() {
	    	setTimeout(function() {
	    		$('.alert').alert('close');
	    	}, 10000);
	    });
    </script>
    @endif

    @section('scripts')

    @show

  </body>
</html>
