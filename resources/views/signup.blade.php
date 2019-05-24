@extends('layouts.base')

@section('css')
<link rel='stylesheet' href='/css/signup.css' />
@endsection

@section('content')
<div class='col-md-6'>
    <h2 class='title'>Register</h2>

    <form action='/signup' method='POST'>
        User Name: <input type='text' name='username' class='form-control form-field' placeholder='User Name' />

        @if (env('POLR_ACCT_CREATION_RECAPTCHA'))
            <div class="g-recaptcha" data-sitekey="{{env('POLR_RECAPTCHA_SITE_KEY')}}"></div>
        @endif

        <input type="hidden" name='_token' value='{{csrf_token()}}' />
        <input type='hidden' name='auth_token' class='form-control form-field' value="{{ session()->get('user')->token }}"/>
        <input type='hidden' name='auth_type' class='form-control form-field' value="{{ session()->get('user')->authType }}"/>
        <input type="submit" class="btn btn-default btn-success" value="Register"/>
        <p class='login-prompt'>
            <small>Already have an account? <a href='{{route('login')}}'>Login</a></small>
        </p>
    </form>
</div>
<div class='col-md-6 hidden-xs hidden-sm'>
    <div class='right-col-one'>
        <h4>Username</h4>
        <p>The username you will use to login to {{env('APP_NAME')}}.</p>
    </p>
</div>
@endsection

@section('js')
    @if (env('POLR_ACCT_CREATION_RECAPTCHA'))
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
@endsection
