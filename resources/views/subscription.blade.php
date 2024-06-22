<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription</title>

    <style>
        body, html {
            height: 100%;
            margin: 300px;
            justify-content: center;
            align-items: center;
            background-color: #f0f0f0; /* Optional background color */
        }

        #subscription-form {
            width: 90%; /* Adjust width as needed */
            max-width: 800px; /* Maximum width for the form */
            padding: 30px;
            background-color: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            text-align: center; /* Center form contents */
        }

        #card-element {
            padding: 40px; /* Padding for the card element */
            background-color: #f7f7f7;
            border-radius: 8px;
            width: 100%; /* Make the card element 100% width */
            max-width: 600px; /* Maximum width for the card element */
            margin: 40px auto; /* Center the Stripe card element */
            box-sizing: border-box; /* Include padding and border in element's total width and height */
            font-size: 18px; /* Adjust font size */
        }

        #submit-button {
            background-color: #4CAF50;
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
    <!-- Stripe.js Library -->
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
<!-- Subscription Form -->
<form id="subscription-form">

    <h2>{{ $product->name }}</h2>
    <p>{{ $product->description }}</p>

    <label for="plan">Select Plan:</label>
    <select id="plan" name="plan">
        @foreach($product->plans as $plan)
            <option value="{{ $plan->stripe_plan_id }}">
                {{$plan->name}}  {{ $plan->amount / 100 }} {{ strtoupper($plan->currency) }}
                / {{ $plan->interval_count }}   {{ $plan->interval }}
            </option>
        @endforeach
    </select>

    <!-- Placeholder for the Stripe Card Element -->
    <div id="card-element"><!-- Stripe Element will be inserted here --></div>
    <!-- Submit Button -->
    <button type="submit" id="submit-button">Subscribe</button>
</form>

<script>
    document.addEventListener('DOMContentLoaded', async () => {
        const stripe = Stripe('{{ env('STRIPE_KEY') }}');
        const elements = stripe.elements(); // hna ba3ml retrive le elstripe element 3a4an ageb 4akl elCard
        const cardElement = elements.create('card');
        cardElement.mount('#card-element');

        const form = document.getElementById('subscription-form');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            /*When a form is submitted, the browser's default behavior is to send a request to the server specified in the form's action*/
            //this function prevent that, and also to send asynchronous request to stripe to check on the payment method and also for validation
            try {
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

                const plan = document.getElementById('plan').value;

                const response = await fetch('/subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        payment_method: setupIntent.payment_method,
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





