<?php

namespace App\Http\Middleware;

use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StandardSubscribe
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\Http\Foundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $standardProduct = Product::where('name', 'Standard Work')->first();
        $premiumProduct = Product::where('name', 'Premium Work')->first();
        $eliteProduct = Product::where('name', 'Elite Work')->first();

        if (!$standardProduct || !$premiumProduct || !$eliteProduct) {
            return redirect('/subscription')->withErrors('Subscription products not found.');
        }

        $standardProductId = $standardProduct->stripe_product_id;
        $premiumProductId = $premiumProduct->stripe_product_id;

        $user = $request->user();
        $subscription = $user->subscriptions->first();

        if ($user->onTrial() ||
            ($subscription && $subscription->stripe_status == 'trialing') ||
            $user->subscribedToProduct($standardProductId) ||
            $user->subscribedToProduct($premiumProductId) ||
        $user->subscribedToProduct($eliteProduct)) {
            return $next($request);
        }

        return redirect('/subscription')->withErrors('You need to subscribe to access this page.');
    }
}
