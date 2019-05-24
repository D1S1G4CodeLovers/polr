<?php
namespace App\Http\Controllers;

use App\Helpers\UserHelper;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Factories\UserFactory;

class AuthController extends Controller {

    /**
     * Redirect the user to the Google authentication page.
     *
     * @return void
     */
    public function redirectToGoogle()
    {
        return Socialite::driver(config('const.socialite.gg'))->with(['prompt' => 'select_account'])->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return void
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $userSocial = Socialite::driver(config('const.socialite.gg'))->stateless()->user();
            $userSocial->authType = config('const.socialite.gg');
        } catch (\Exception $e) {
            \Log::error($e);
            return redirect(route('login'))->with('error', 'Something went wrong!');
        }

        $email = $userSocial->getEmail();

        if (!UserHelper::emailExists($email)) {
            if (env('SETTING_RESTRICT_EMAIL_DOMAIN')) {
                $emailDomain = explode('@', $email)[1];

                $permittedEmailDomains = explode(',', env('SETTING_ALLOWED_EMAIL_DOMAINS'));

                if (!in_array($emailDomain, $permittedEmailDomains)) {
                    return redirect(route('login'))->with('error', 'Sorry, email\'s domain must contain ' . env('SETTING_ALLOWED_EMAIL_DOMAINS') . '.');
                }
            }

            return redirect(route('signup'))->with('user', $userSocial);
        }

        $user = UserHelper::getUserBy('email', $email, env('POLR_ACCT_ACTIVATION'));

        $request->session()->put('username', $user->username);
        $request->session()->put('role', $user->role);

        return redirect()->route('index');
    }
}
