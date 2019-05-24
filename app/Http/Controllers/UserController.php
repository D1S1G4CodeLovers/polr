<?php
namespace App\Http\Controllers;
use Mail;
use Hash;
use Illuminate\Http\Request;
use Validator;

use App\Helpers\CryptoHelper;
use App\Helpers\UserHelper;

use App\Factories\UserFactory;
use Laravel\Socialite\Facades\Socialite;

class UserController extends Controller {
    /**
     * Show pages related to the user control panel.
     *
     * @return Response
     */
    public function displayLoginPage(Request $request) {
        if ($request->session()->has('username') && $request->session()->has('role')) {
            return redirect(route('index'));
        }

        return view('login');
    }

    public function displaySignupPage(Request $request) {
        if (!$request->session()->has('user')) {
            return redirect(route('login'))->with('error', 'Something went wrong!');
        }

        return view('signup');
    }

    public function displayLostPasswordPage(Request $request) {
        return view('lost_password');
    }

    public function performLogoutUser(Request $request) {
        $request->session()->forget('username');
        $request->session()->forget('role');
        return redirect()->route('index');
    }

    public function performLogin(Request $request) {
        $username = $request->input('username');
        $password = $request->input('password');

        $credentials_valid = UserHelper::checkCredentials($username, $password);

        if ($credentials_valid != false) {
            // log user in
            $role = $credentials_valid['role'];
            $request->session()->put('username', $username);
            $request->session()->put('role', $role);

            return redirect()->route('index');
        }
        else {
            return redirect('login')->with('error', 'Invalid password or inactivated account. Try again.');
        }
    }

    /**
     * Sign up user
     *
     * @param  Request $request
     *
     * @return mixed
     */
    public function performSignup(Request $request) {
        if (env('POLR_ALLOW_ACCT_CREATION') == false) {
            return redirect(route('index'))->with('error', 'Sorry, but registration is disabled.');
        }

        if (env('POLR_ACCT_CREATION_RECAPTCHA')) {
            // Verify reCAPTCHA if setting is enabled
            $gRecaptchaResponse = $request->input('g-recaptcha-response');

            $recaptcha = new \ReCaptcha\ReCaptcha(env('POLR_RECAPTCHA_SECRET_KEY'));
            $recaptchaResp = $recaptcha->verify($gRecaptchaResponse, $request->ip());

            if (!$recaptchaResp->isSuccess()) {
                return redirect(route('signup'))->with('error', 'You must complete the reCAPTCHA to register.');
            }
        }

        $inputs = $request->input();
        $userSocialite = Socialite::driver($inputs['auth_type'])->userFromToken($inputs['auth_token']);
        $userSocialite->authType = $inputs['auth_type'];

        // Validate signup form data
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:users|alpha_dash'
        ]);

        if ($validator->fails()) {
            return redirect(route('signup'))
                ->withErrors($validator)
                ->with('user', $userSocialite);
        }

        $ip = $request->ip();
        $acctActivationNeeded = env('POLR_ACCT_ACTIVATION');

        if ($acctActivationNeeded == false) {
            // if no activation is necessary
            $active = 1;
            $response = redirect(route('login'))->with('success', 'Thanks for signing up! You may now log in.');
        } else {
            // email activation is necessary
            $active = 0;
            $response = redirect(route('login'))->with('success', 'Thanks for signing up! Please confirm your email to continue.');
        }

        $apiActive = false;
        $apiKey = null;

        if (env('SETTING_AUTO_API')) {
            // if automatic API key assignment is on
            $apiActive = 1;
            $apiKey = CryptoHelper::generateRandomHex(env('_API_KEY_LENGTH'));
        }

        $user = UserFactory::createUser($inputs['username'], $userSocialite->email, null, $active, $ip, $apiKey, $apiActive);

        if ($acctActivationNeeded) {
            Mail::send('emails.activation', [
                'username' => $user->username, 'recovery_key' => $user->recovery_key, 'ip' => $ip
            ], function ($m) use ($user) {
                $m->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));

                $m->to($user->email, $user->username)->subject(env('APP_NAME') . ' account activation');
            });
        }

        return $response;
    }

    public function performSendPasswordResetCode(Request $request) {
        if (!env('SETTING_PASSWORD_RECOV')) {
            return redirect(route('index'))->with('error', 'Password recovery is disabled.');
        }

        $email = $request->input('email');
        $ip = $request->ip();
        $user = UserHelper::getUserByEmail($email);

        if (!$user) {
            return redirect(route('lost_password'))->with('error', 'Email is not associated with a user.');
        }

        $recovery_key = UserHelper::resetRecoveryKey($user->username);

        Mail::send('emails.lost_password', [
            'username' => $user->username, 'recovery_key' => $recovery_key, 'ip' => $ip
        ], function ($m) use ($user) {
            $m->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));

            $m->to($user->email, $user->username)->subject(env('APP_NAME') . ' Password Reset');
        });

        return redirect(route('index'))->with('success', 'Password reset email sent. Check your inbox for details.');
    }

    public function performActivation(Request $request, $username, $recovery_key) {
        $user = UserHelper::getUserByUsername($username, true);

        if (UserHelper::userResetKeyCorrect($username, $recovery_key, true)) {
            // Key is correct
            // Activate account and reset recovery key
            $user->active = 1;
            $user->save();

            UserHelper::resetRecoveryKey($username);
            return redirect(route('login'))->with('success', 'Account activated. You may now login.');
        }
        else {
            return redirect(route('index'))->with('error', 'Username or activation key incorrect.');
        }
    }

    public function performPasswordReset(Request $request, $username, $recovery_key) {
        $new_password = $request->input('new_password');
        $user = UserHelper::getUserByUsername($username);

        if (UserHelper::userResetKeyCorrect($username, $recovery_key)) {
            if (!$new_password) {
                return view('reset_password');
            }

            // Key is correct
            // Reset password
            $user->password = Hash::make($new_password);
            $user->save();

            UserHelper::resetRecoveryKey($username);
            return redirect(route('login'))->with('success', 'Password reset. You may now login.');
        }
        else {
            return redirect(route('index'))->with('error', 'Username or reset key incorrect.');
        }

    }
}
