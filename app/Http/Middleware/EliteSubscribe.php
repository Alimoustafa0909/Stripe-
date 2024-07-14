<?php

namespace App\Http\Middleware;

use App\Models\Plan;
use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EliteSubscribe
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\Http\Foundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (!Auth::check()) {
            return redirect('/login');
        }

        $eliteProduct = Product::where('name', 'Elite Work')->first();


        $eliteProductId = $eliteProduct->stripe_product_id;



        $subscription = $user->subscriptions->first();

        if ($user->onTrial() ||
            ($subscription && $subscription->stripe_status == 'trialing') ||
            $user->subscribedToProduct($eliteProductId,$eliteProductId)) {
            return $next($request);
        }

        return redirect('/subscription')->withErrors('You need to subscribe to Elite Package to access this page.');
    }
}
