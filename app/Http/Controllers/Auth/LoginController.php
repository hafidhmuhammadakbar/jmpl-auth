<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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
        
        // Check if email exists in the request
        $email = $request->input('email');
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            // Email not registered
            return redirect()->back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'This email is not registered.',
                ]);
        }
    
        // Email exists, proceed with authentication
        if ($attempts < 3) {
            $credentials = $request->only('email', 'password');    
        }
        else {
            // Validate reCAPTCHA
            $response = $request->input('g-recaptcha-response');
            $captchaResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => '6LcOj5kpAAAAABQTXpSWxrm5N7h74lNpvkenNqTY',
                'response' => $response,
            ]);
            
            if (!$captchaResponse->json('success')) {
                return redirect()->back()
                    ->withInput($request->only('email'))
                    ->withErrors([
                        'g-recaptcha' => 'Please fill the recapthca.',
                    ]);
            }
            
            $credentials = $request->only('email', 'password');
        }
        
        if (Auth::attempt($credentials)) {
            $this->clearLoginAttempts($request);
            
            // Authentication passed
            return redirect()->intended('home');
        }
    
        // Increment login count on failed login
        $this->incrementLoginAttempts($request);
    
        // Redirect back with error message for incorrect password
        return redirect()->back()
            ->withInput($request->only('email'))
            ->withErrors([
                'password' => 'The password entered is incorrect.',
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
