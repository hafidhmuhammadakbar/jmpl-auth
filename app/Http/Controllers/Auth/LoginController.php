<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

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
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request)
    {
        $attempts = $request->session()->get('loginAttemptCount', 0);
        if ($attempts < 3) {
            $credentials = $request->only('email', 'password');    
        }
        else {
            $credentials = $request->only('email', 'password', 'g-recaptcha');
        }
        
        if (Auth::attempt($credentials)) {
            $this->clearLoginAttempts($request);
            
            // Authentication passed
            return redirect()->intended('home');
        }

        // Increment login count on failed login
        $this->incrementLoginAttempts($request);

        // Redirect back with error message
        return redirect()->back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => 'These credentials do not match our records.',
            ]);
    }

    protected function incrementLoginAttempts(Request $request)
    {
        $attempts = $request->session()->get('loginAttemptCount', 0) + 1;
        $request->session()->put('loginAttemptCount', $attempts);
    }

    protected function clearLoginAttempts(Request $request)
    {
        $request->session()->forget('loginAttemptCount');
    }
}
