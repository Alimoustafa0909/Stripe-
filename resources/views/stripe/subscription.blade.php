<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription</title>

    <style>
        .card {
            width: 800px;
            margin: 50px auto;
            padding: 40px;
            border: 1px solid #ccc;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        body, html {
            height: 100%;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f0f0f0; /* Optional background color */
        }

        #subscription-form {
            width: 90%; /* Adjust width as needed */
            max-width: 1000px; /* Maximum width for the form */
            padding: 50px;
            background-color: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            text-align: center; /* Center form contents */
        }

        #card-element {
            padding: 20px; /* Padding for the card element */
            background-color: #f7f7f7;
            border-radius: 8px;
            width: 100%; /* Make the card element 100% width */
            max-width: 600px; /* Maximum width for the card element */
            margin: 20px auto; /* Center the stripe card element */
            box-sizing: border-box; /* Include padding and border in element's total width and height */
            font-size: 18px; /* Adjust font size */
        }

        #submit-button {
            background-color: #4CAF50;
            margin-top: 50px;
            color: white;
            border: none;
            padding: 15px 30px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 18px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        #submit-button:hover {
            background-color: #45a049;
        }
    </style>
    <!-- stripe.js Library -->
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
<div class="card">
    <h2> Product :{{ $product->name }}</h2>
    <p> Description: {{ $product->description }}</p>

    <form id="subscription-form">
        <label for="plan">Select Plan:</label>
        <select id="plan" name="plan">
            @foreach($product->plans as $plan)
                <option value="{{ $plan->stripe_plan_id }}">
                    {{$plan->name}}  {{ $plan->amount / 100 }} {{ strtoupper($plan->currency) }}
                    / {{ $plan->interval_count }}   {{ $plan->interval }}
                </option>
            @endforeach
        </select>

        @if ($defaultPaymentMethod)
            <label for="payment-method">Select Payment Method:</label>
            <select id="payment-method" name="payment_method">
                @if ($defaultPaymentMethod)
                    <option value="{{ $defaultPaymentMethod->stripe_payment_method_id }}">
                        {{ $defaultPaymentMethod->pm_type }} ending in {{ $defaultPaymentMethod->pm_last_four }} (Default)
                    </option>
                @endif
                @foreach ($paymentMethods as $paymentMethod)
                    @if (!$paymentMethod->default)
                        <option value="{{ $paymentMethod->stripe_payment_method_id }}">
                            {{ $paymentMethod->pm_type }} ending in {{ $paymentMethod->pm_last_four }}
                        </option>
                    @endif
                @endforeach
            </select>
        @else
            <!-- Placeholder for the stripe Card Element -->
            <div id="card-element"><!-- stripe Element will be inserted here --></div>
        @endif

        <!-- Submit Button -->
        <button type="submit" id="submit-button">Subscribe</button>

        <a id="submit-button" href="{{ route('payment-methods.index') }} ">Manage Payment Methods</a>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', async () => {
        @if (!$defaultPaymentMethod)
        const stripe = Stripe('{{ env('STRIPE_KEY') }}');
        const elements = stripe.elements(); // hna ba3ml retrive le elstripe element 3a4an ageb 4akl elCard
        const cardElement = elements.create('card');
        cardElement.mount('#card-element');
        @endif

        const form = document.getElementById('subscription-form');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            /*When a form is submitted, the browser's default behavior is to send a request to the server specified in the form's action*/
            //this function prevent that, and also to send asynchronous request to stripe to check on the payment method and also for validation
            try {
                let paymentMethod;
                @if (!$defaultPaymentMethod)
                const {setupIntent, error} = await stripe.confirmCardSetup( /*
                setupIntent: This object contains information about the setup intent,
                which represents the setup of a customer's payment method for future payments

                confirmCardSetup : It confirms the setup of a card payment method using a SetupIntent.
                */
                    '{{ $clientSecret }}',
                    {
                        payment_method: {
                            card: cardElement,
                        }
                    }
                );

                if (error) {
                    console.error('Error confirming card setup:', error.message);
                    return;
                }

                paymentMethod = setupIntent.payment_method;
                @else
                    paymentMethod = document.getElementById('payment-method').value;
                @endif

                const plan = document.getElementById('plan').value;

                const response = await fetch('/subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        payment_method: paymentMethod,
                        plan: plan
                    }),
                });
                // i fetch on this URL and send to the Controller the payment_method and the plan

                const result = await response.json();

                if (result.error) {
                    console.error('Error creating subscription:', result.error);
                } else {
                    console.log('Subscription successful!');
                }
            } catch (error) {
                console.error('Unexpected error:', error);
            }
        });
    });
</script>
</body>
</html>
