<?php


namespace App\Http\Controllers\Stripe;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Product;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $products = Product::with('plans')->get();
        if (!$products) {
            abort(404, 'Product not found');
        }

        $intent = SetupIntent::create();
        $clientSecret = $intent->client_secret;
        //Get the specific product with its plans
        $paymentMethods = $user->paymentMethods;
        $defaultPaymentMethod = $user->defaultPaymentMethod;

        $userSub = $user->subscriptions->first();

        $price = null;
        if ($userSub) {
            $userPlanId = $userSub->stripe_price;
            $price = Plan::where('stripe_plan_id', $userPlanId)->first();
        }

        $endTime = true;
        if ($userSub) {
            if ($userSub->ends_at && $userSub->ends_at->lt(Carbon::now())) {
                $endTime = false;
            }
        }

        return view('stripe.subscription', compact(
            'clientSecret',
            'user',
            'paymentMethods',
            'defaultPaymentMethod',
            'price',
            'productId',
            'endTime',
            'userSub',
            'products'
        ));
    }

    public function subscribe(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'User not authenticated.'], 401);
            }

            $productId = $request->input('product'); // Get the product ID from the request
            $plan = $request->input('plan'); // Get the plan ID from the request
            $paymentMethod = $request->payment_method;


            Stripe::setApiKey(config('services.stripe.secret'));

            $user->createOrGetStripeCustomer();
            $user->updateDefaultPaymentMethod($paymentMethod); // Update default payment method if changed

            // Check if the user has any existing subscription
            $subscription = $user->subscriptions->first();

            if ($subscription && $subscription->stripe_price === $plan && $subscription->onGracePeriod()) {
                // Resume the subscription
                $subscription->resume();
                return response()->json(['success' => true, 'message' => 'Subscription resumed successfully!']);
            }

            if ($subscription) {
                // Check if the user is already subscribed to the same plan
                if ($user->subscribedToPrice($plan, $subscription->type)) {
                    return response()->json(['error' => 'You are already subscribed to this plan. You can select another plan.'], 400);
                }

                // Swap the existing subscription to the new plan
                $subscription->swap($plan);
                return response()->json(['success' => true, 'message' => 'Subscription plan swapped successfully!']);
            }

//             Check if the user is on a trial period
            if ($user->onGenericTrial()) {
                $trialEndsAt = $user->trialEndsAt();

                // Ensure the trial end is in the future
                $user->newSubscription($productId, $plan)
                    ->trialUntil($trialEndsAt)
                    ->create($paymentMethod);
                return response()->json(['success' => true, 'message' => 'Subscription created and will start after the trial period ends!']);
            }


            // Create a new subscription without a trial period
            $user->newSubscription($productId, $plan)
                ->create($paymentMethod);

            return response()->json(['success' => true, 'message' => 'Subscription created successfully!']);
        } catch (\Exception $e) {
            // Log the detailed error message
            \Log::error('Subscription error: ' . $e->getMessage(), ['exception' => $e]);

            // Return a JSON response with the error message
            return response()->json(['error' => 'An error occurred while processing your subscription. Please try again later.'], 500);
        }
    }

    public function cancelSubscription(Request $request)
    {
        $user = auth()->user();

        $subscription = $user->subscriptions->first();

        if ($subscription) {
            $subscription->cancel();
            return back()->with('success', 'Subscription canceled successfully.');
        } else {
            return back()->with('error', 'No active subscription found.');
        }
    }
    public function resumeSubscription(Request $request)
    {
        $user = auth()->user();

        $subscription = $user->subscriptions->first();
        if ($subscription && $subscription->onGracePeriod()) {
            $subscription->resume();
            return back()->with(['success' => true, 'message' => 'Subscription resumed successfully!']);

        } else {
            return back()->with('error', 'No active subscription found.');
        }
    }


}












//public function subscribe(Request $request)
//{
//    $user = auth()->user();
//    $productId = Product::latest()->first()->stripe_product_id;
//
//    $paymentMethod = $request->payment_method;
//    $plan = $request->input('plan');
//    Stripe::setApiKey(config('services.stripe.secret'));
//
//    $user->createOrGetStripeCustomer();
//    $user->updateDefaultPaymentMethod($paymentMethod); // Update default payment method if changed
//
//    // Check if the user has a subscription that was canceled but is still in the grace period
//    $subscription = $user->subscription($productId);
//
//    if ($subscription && $subscription->stripe_price === $plan && $subscription->onGracePeriod()) {
//        // Resume the subscription
//        $subscription->resume();
//        return response()->json(['success' => true, 'message' => 'Subscription resumed successfully!']);
//    }
//
//    if ($user->subscribed($productId)) {
//        if ($user->subscribedToPrice($plan, $productId)) {
//            return response()->json(['error' => 'You are already subscribed to this plan. You can select another plan.'], 400);
//        }
//        $user->subscription($productId)->swap($plan);
//        return response()->json(['success' => true, 'message' => 'Subscription plan swapped successfully!']);
//    }
//
////         Check if the user is on a trial period
//    if ($user->onGenericTrial()) {
//        $trialEndsAt = $user->trialEndsAt();
//        $user->newSubscription($productId, $plan)
//            ->trialUntil($trialEndsAt)
//            ->create($paymentMethod);
//
//        return response()->json(['success' => true, 'message' => 'Subscription created and will start after the trial period ends!']);
//    }
//
//    // Create a new subscription without a trial period
//    $user->newSubscription($productId, $plan)
//        ->create($paymentMethod);
//
//    return response()->json(['success' => true, 'message' => 'Subscription created successfully!']);
//}






