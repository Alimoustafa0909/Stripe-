<?php

namespace App\Listeners;

use App\Models\PaymentMethod;
use App\Models\Plan;
use Laravel\Cashier\Events\WebhookReceived;
use App\Models\Product;

class StripeEventListener
{
    /**
     * Handle received stripe webhooks.
     */
    public function handle(WebhookReceived $event): void
    {
        // Extract payload from the event
        $payload = $event->payload;
        $productData = $payload['data']['object'];

        // Handle specific events
        if ($payload['type'] === 'product.created') {
            // Create or update product in your database
            $product = new Product();
            $product->name = $productData['name'];
            $product->description = $productData['description'];
            // $product->image = $productData['images'];
            $product->stripe_product_id = $productData['id']; // stripe product ID
            // Other fields as needed
            $product->save();
        } elseif ($payload['type'] === 'product.deleted') {
            $stripeProductId = $productData['id'];
            // Find the product by its stripe product ID and delete it
            $product = Product::where('stripe_product_id', $stripeProductId)->first();

            if ($product) {
                $product->delete();
            }

        } elseif ($payload['type'] === 'product.updated') {
            $stripeProductId = $productData['id'];
            // Find the product by its stripe product ID
            $product = Product::where('stripe_product_id', $stripeProductId)->first();

            if ($product) {
                // Update the product details
                $product->name = $productData['name'];
                $product->description = $productData['description'];
                // $product->image = $productData['images']; // Assuming images are stored differently
                // Update other fields as needed
                $product->save();
            }
        } elseif ($payload['type'] === 'price.created') {
            $plan = new Plan();

            $plan->product_id = Product::where('stripe_product_id', $productData['product'])->first()->id;
            $plan->name = $productData['nickname'];
            $plan->stripe_product_id = $productData['product']; // stripe product ID
            $plan->stripe_plan_id = $productData['id'];
            $plan->currency = $productData['currency'];
            $plan->amount = $productData['unit_amount'];
            $plan->interval_count = $productData['recurring']['interval_count'];
            $plan->interval = $productData['recurring']['interval'];
            $plan->save();

        } elseif ($payload['type'] === 'price.updated') {
            $stripePlanId = $productData['id'];
            $plan = Plan::where('stripe_plan_id', $stripePlanId)->first();

            if ($plan) {
                $plan->name = $productData['nickname'];
                $plan->currency = $productData['currency'];
                $plan->amount = $productData['unit_amount'];
                $plan->interval_count = $productData['recurring']['interval_count'];
                $plan->interval = $productData['recurring']['interval'];
                $plan->save();
            }
        } elseif ($payload['type'] === 'price.deleted') {
            $stripePlanId = $productData['id'];
            // Find the product by its stripe product ID and delete it
            $plan = Plan::where('stripe_plan_id', $stripePlanId)->first();

            if ($plan) {
                $plan->delete();
            }

        }elseif ($payload['type'] === 'payment_method.attached') {
            $payment = new PaymentMethod();
            $payment->stripe_payment_method_id= $productData['id'];
            $payment->pm_type= $productData['card']['brand'];
            $payment->pm_last_four= $productData['card']['last4'];
            $payment->expires_at= $productData['card']['exp_year'];
            $payment->save();

        }
            // You can add more conditions based on different webhook types
    }
}
