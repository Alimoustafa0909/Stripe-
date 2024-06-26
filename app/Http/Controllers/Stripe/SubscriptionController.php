<?php



namespace App\Http\Controllers\Stripe;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Stripe\SetupIntent;
use Stripe\Stripe;

class SubscriptionController extends Controller
{
    public function showSubscriptionForm()
    {

        Stripe::setApiKey(config('services.stripe.secret'));

        $firstProduct = Product::latest()->first();
        $productId =$firstProduct->stripe_product_id;
        $intent = SetupIntent::create();
        //Get the specific product with its plans
        $product = Product::with('plans')->where('stripe_product_id', $productId)->first();

        if (!$product) {
            // Handle the case where the product is not found
            abort(404, 'Product not found');
        }

        $user = auth()->user();

        $paymentMethods = $user->paymentMethods;
        $defaultPaymentMethod = $user->defaultPaymentMethod;
        $clientSecret = $intent->client_secret;

        return view('stripe.subscription', compact('clientSecret', 'product','paymentMethods','defaultPaymentMethod'));
    }

    public function subscribe(Request $request)
    {
        $user = auth()->user();

        $paymentMethod = $request->payment_method;
        $plan = $request->input('plan');

        Stripe::setApiKey(config('services.stripe.secret'));

        // Attach the payment method to the user
        $user->createOrGetStripeCustomer();/*This Function ensures that the authenticated user has a corresponding customer record in stripe.
    if not he make a record for this user */
        $user->updateDefaultPaymentMethod($paymentMethod);//if the default payment method of the user has been changed


        $subscription = $user->newSubscription('default', $plan)
            ->create($paymentMethod);

        return response()->json(['success' => true]);
    }
}
