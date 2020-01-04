<?php

namespace App\Http\Controllers\Auth;

use App\Base\OAuth\OAuthManager;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Socialite;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Login username to be used by the controller.
     *
     * @var string
     */
    protected $username;


    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->username = $this->findUsername();
    }


    public function redirectToProvider($driver)
    {

        if (!in_array($driver,['qq','github'])){
            abort(404);
        }

        return Socialite::driver($driver)->redirect();
    }


    public function handleProviderCallback($driver)
    {

        $user = Socialite::driver($driver)->user();
        $manager = new OAuthManager($driver);
        $manager->auth($user);

        return redirect('/');
        // $user->token;
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function findUsername()
    {
        $login = request()->input('login');

        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        request()->merge([$fieldType => $login]);

        return $fieldType;
    }

    /**
     * Get username property.
     *
     * @return string
     */
    public function username()
    {
        return $this->username;
    }
}
