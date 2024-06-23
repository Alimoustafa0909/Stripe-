<?php


namespace App\Http\Controllers\Stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use Stripe\Stripe;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $paymentMethods = $user->paymentMethods;
        $defaultPaymentMethod = $user->defaultPaymentMethod;

        return view('payment-methods.index', compact('paymentMethods', 'defaultPaymentMethod'));
    }

    public function setDefault(Request $request)
    {
        $user = auth()->user();
        $paymentMethodId = $request->input('payment_method_id');

        $user->paymentMethods()->update(['default' => false]);

        $paymentMethod = $user->paymentMethods()->where('id', $paymentMethodId)->first();
        $paymentMethod->default = true;
        $paymentMethod->save();

        return redirect()->route('payment-methods.index')->with('success', 'Default payment method updated.');
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        Stripe::setApiKey(config('services.stripe.secret'));

        $paymentMethod = $request->input('payment_method');

        $user->createOrGetStripeCustomer();
        $user->updateDefaultPaymentMethod($paymentMethod);

        return response()->json(['success' => true]);
    }
}
