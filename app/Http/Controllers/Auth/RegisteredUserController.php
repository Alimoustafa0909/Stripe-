<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Stripe\Stripe;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Stripe::setApiKey(env('STRIPE_SECRET'));

        $product = Product::latest()->first();
        if (!$product) {
            // Handle case where no product is found
            return redirect()->back()->with('error', 'No product found.');
        }

        $productId = $product->stripe_product_id;
        $standardPlan = $product->plans()->where('name', 'Elite')->first();

        if (!$standardPlan) {
            // Handle case where no standard plan is found
            return redirect()->back()->with('error', 'Standard plan not found.');
        }

        $user->newSubscription($productId, $standardPlan->stripe_plan_id)
            ->trialDays(1)
            ->create();

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard'));
    }
}
