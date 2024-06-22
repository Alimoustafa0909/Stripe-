<?php



namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Product;
use http\Message;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\SetupIntent;
use App\Models\User;
use function Laravel\Prompts\error;

class SubscriptionController extends Controller
{
    public function showSubscriptionForm()
    {

        Stripe::setApiKey(config('services.stripe.secret'));

        $productId = 'prod_QKm99TDT8AFm9Q'; // Replace with the actual product ID that you want to show
        $intent = SetupIntent::create();
        //Get the specific product with its plans
        $product = Product::with('plans')->where('stripe_product_id', $productId)->first();

        if (!$product) {
            // Handle the case where the product is not found
            abort(404, 'Product not found');
        }

        $clientSecret = $intent->client_secret;

        return view('subscription', compact('clientSecret', 'product'));
    }

    public function subscribe(Request $request)
    {
        $user = auth()->user();
        $paymentMethod = $request->payment_method;
        $plan = $request->input('plan');

        Stripe::setApiKey(config('services.stripe.secret'));

        // Attach the payment method to the user
        $user->createOrGetStripeCustomer();/*This Function ensures that the authenticated user has a corresponding customer record in Stripe.
    if not he make a record for this user */
        $user->updateDefaultPaymentMethod($paymentMethod);//if the payment method of the user has been changed


        $subscription = $user->newSubscription('default', $plan)
            ->trialDays(1)
            ->create($paymentMethod);

        return response()->json(['success' => true]);
    }
}
