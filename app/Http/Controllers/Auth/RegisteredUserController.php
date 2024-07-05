<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\SetupIntent;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $clientSecret = $this->createSetupIntent()->client_secret;
        return view('auth.register', compact('clientSecret'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $paymentMethod = $request->payment_method;
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'payment_method' => ['required', 'string'],
        ]);

        Stripe::setApiKey(config('services.stripe.secret'));


        // Create User and set trial_ends_at
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'trial_ends_at' => now()->addDays(7), // Set trial period here
        ]);

        $user->createOrGetStripeCustomer();
        $user->updateDefaultPaymentMethod($paymentMethod);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard'));
    }

    protected function createSetupIntent()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        return SetupIntent::create();
    }
}
