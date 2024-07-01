<?php


namespace App\Http\Controllers\Stripe;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Http\Request;
use Stripe\SetupIntent;
use Stripe\Stripe;
use function Laravel\Prompts\error;

class SubscriptionController extends Controller
{
    public function showSubscriptionForm()
    {
        $user = auth()->user();

        Stripe::setApiKey(config('services.stripe.secret'));

        $productId = Product::latest()->first()->stripe_product_id;

        if (!$productId) {
            // Handle the case where the product is not found
            abort(404, 'Product not found');
        }

        $intent = SetupIntent::create();
        //Get the specific product with its plans
        $product = Product::with('plans')->where('stripe_product_id', $productId)->first();

//        $subscription_user= $user->subscriptions()->first();
        $paymentMethods = $user->paymentMethods;
        $defaultPaymentMethod = $user->defaultPaymentMethod;

        $clientSecret = $intent->client_secret;

        $userSub = $user->subscription($productId);
        $price = null;

        if ($userSub) {
            $userPlanId = $userSub->stripe_price;
            $price = Plan::where('stripe_plan_id', $userPlanId)->first();
        }

//
//        $userSub = $user->subscription($productId);
//
//        $userPlanId = $userSub->stripe_price;
//        $price = Plan::where('stripe_plan_id' ,$userPlanId)->first();

        return view('stripe.subscription', compact(
            'clientSecret',
            'product',
            'paymentMethods',
            'defaultPaymentMethod',
            'price',
            'productId'
        ));
    }

    public function subscribe(Request $request)
    {
        $user = auth()->user();
        $productId = Product::latest()->first()->stripe_product_id;

        $paymentMethod = $request->payment_method;
        $plan = $request->input('plan');
        Stripe::setApiKey(config('services.stripe.secret'));

        if ($user->subscribed($productId)) {

            if ($user->subscribedToPrice($plan, $productId)) {
                return response()->json(['error' => 'You are already subscribed to this plan You can Select Another Plan'], 400);
            }

            $user->subscription($productId)->swap($plan);
            return response()->json(['success' => true, 'message' => 'Subscription plan swapped successfully!']);
        }
        $user->createOrGetStripeCustomer();

        $user->updateDefaultPaymentMethod($paymentMethod);//if the default payment method of the user has been changed

        $user->newSubscription($productId, $plan)
            ->create($paymentMethod);

        return response()->json(['success' => true]);
    }

    public function cancelSubscription(Request $request)
    {
        $productId = Product::latest()->first()->stripe_product_id;

        $user = auth()->user();
        $user->subscription($productId)->cancel();
        return back()->with('success', 'Subscription canceled successfully.');

    }


}
