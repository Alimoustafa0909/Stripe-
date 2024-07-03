<?php

namespace App\Http\Middleware;

use App\Models\Plan;
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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $productId = Product::latest()->first()->stripe_product_id;
        $standardPlan = Plan::where('name', 'Standard')->first()->stripe_plan_id;
        $premiumPlan = Plan::where('name', 'Premium')->first()->stripe_plan_id;
        $elitePlan = Plan::where('name', 'Elite')->first()->stripe_plan_id;


        if ($request->user()->subscribedToPrice($standardPlan, $productId) ||
            $request->user()->subscribedToPrice($premiumPlan, $productId) ||
            $request->user()->subscribedToPrice($elitePlan, $productId)){
            return $next($request);
        }

        return redirect('/subscription')->withErrors('You need to subscribe to access this page.');
    }
}
