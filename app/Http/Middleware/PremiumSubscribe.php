<?php

namespace App\Http\Middleware;

use App\Models\Plan;
use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PremiumSubscribe
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $productId = Product::latest()->first()->stripe_product_id;
        $plan = Plan::where('name', 'Premium')->first();
        $premiumPlan = $plan->stripe_plan_id;

        if ($request->user()->subscribedToPrice($premiumPlan, $productId) || $request->user()->subscribedToPrice('price_1PYEtsH1nccn6k3zBaMfp0GH', $productId)) {
            return $next($request);
        }

        return redirect('/subscription')->withErrors('You need to subscribe to the Premium or Elite Package to access this page.');
    }
}
