@extends('layouts.base')

@section('css')
<link rel='stylesheet' href="{{ url('css/login.css') }}" />
<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Roboto:400" />
@endsection

@section('content')
<div class="center-text">
    <h1>Login</h1><br/><br/>
    <div class="col-md-3"></div>
    <div class="col-md-6">
        <form action="login" method="POST">
            <input type="text" placeholder="username" name="username" class="form-control login-field" />
            <input type="password" placeholder="password" name="password" class="form-control login-field" />
            <input type="hidden" name='_token' value='{{csrf_token()}}' />
            <button type="submit" value="Login" class="login-submit btn btn-success"><p class="btn-text"><b>Login</b></p></button>
            <div class="google-submit">
                <a class="google-btn" href="{{ route('googleAuth') }}">
                    <span class="google-icon-wrapper">
                      <img class="google-icon-svg" src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg"/>
                    </span>
                    <p class="btn-text"><b>Login with Google</b></p>
                </a>
            </div>

            <p class='login-prompts'>
            @if (env('SETTING_PASSWORD_RECOV') == true)
                <small>Forgot your password? <a href='{{route('lost_password')}}'>Reset</a></small>
            @endif
            </p>
        </form>
    </div>
    <div class="col-md-3"></div>
</div
@endsection
