<!DOCTYPE html>
<html lang="en">
<head>
    @php
        use Carbon\Carbon;
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        .card {
            width: 800px;
            margin: 0 auto; /* Center the card element */
            padding: 40px;
            border: 1px solid #ccc;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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

        .loading-spinner {
            display: none; /* Hide the spinner by default */
            margin-left: 10px;
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

        #submit-button:disabled {
            background-color: #cccccc; /* Disabled button color */
            cursor: not-allowed; /* Show not-allowed cursor */
        }

        #success-message {
            margin-top: 20px;
            font-size: 18px;
            color: green;
            display: none;
        }

        #error-message {
            color: red;
            margin-top: 20px;
            font-weight: bold;
        }

        .subscription-header {
            width: 100%;
            background-color: #f0f0f0;
            padding: 20px 0;
            text-align: center;
            margin-bottom: 20px;
        }

        #subscription-details {
            width: 90%;
            max-width: 600px;
            margin: 0 auto;
            border-collapse: collapse;
            border: 1px solid #ccc;
            border-radius: 8px;
            overflow: hidden;
        }

        #subscription-details th,
        #subscription-details td {
            padding: 10px;
            border-bottom: 1px solid #ccc;
            text-align: left;
        }

        #subscription-details th {
            background-color: #f0f0f1;
        }

        .cancel-button {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }

        .cancel-button:hover {
            background-color: #d32f2f;
        }

    </style>    <!-- stripe.js Library -->
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>

@if ($errors->any())
    <div id="error-message">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($price && $endTime)
    <div class="subscription-header">
        <h3>Current Subscription Plan:</h3>
        <table id="subscription-details">

            <tr>
                @if(!$user->subscription($productId)->onTrial())
                <th>Name</th>
                <th>Amount</th>
                <th>Currency</th>
                <th>Interval</th>
                <th>Ends At</th>
                @endif
                <th>Trialing Until</th>
            </tr>
            <tr>
                @if(!$user->subscription($productId)->onTrial())
                <td>{{ $price->name }}</td>
                <td>{{ $price->amount / 100 }}$</td>
                <td>{{ strtoupper($price->currency) }}</td>
                <td>{{ $price->interval_count }} {{ $price->interval }}</td>
                <td>{{ $user->subscription($productId)->ends_at ? ($user->subscription($productId)->ends_at) : 'N/A' }}</td>
                @endif
                <td>{{ $user->subscription($productId)->trial_ends_at ? ($user->subscription($productId)->trial_ends_at) : 'N/A' }}</td>

            </tr>
        </table>
        <form id="cancel-subscription-form" action="{{ route('cancel_subscription') }}" method="POST">
            @csrf
            <input type="hidden" name="_method">
            @if($user->subscription($productId)->trial_ends_at)
                <h2>You are on Trial Mode Now Enjoy it </h2>
            @elseif(!$user->subscription($productId)->ends_at)
                <button type="submit" class="cancel-button">Cancel Subscription</button>
            @endif

        </form>
    </div>
@endif

<div class="card">
    <div class="error-message" id="error-message"></div>
    <div class="success-message" id="success-message"></div>
    <h2>Product: {{ $product->name }}</h2>
    <p>Description: {{ $product->description }}</p>
    <form id="subscription-form">
        <label for="plan">Select Plan:</label>
        <select id="plan" name="plan">
            @foreach($product->plans as $plan)
                <option value="{{ $plan->stripe_plan_id }}" data-plan-name="{{ $plan->name }}">
                    {{$plan->name}}  {{ $plan->amount / 100 }} {{ strtoupper($plan->currency) }}
                    / {{ $plan->interval_count }}   {{ $plan->interval }}
                </option>
            @endforeach
        </select>

        @if ($defaultPaymentMethod)
            <label for="payment-method">Select Payment Method:</label>
            <select id="payment-method" name="payment_method">
                <option value="{{ $defaultPaymentMethod->stripe_payment_method_id }}">
                    {{ $defaultPaymentMethod->pm_type }} ending in {{ $defaultPaymentMethod->pm_last_four }}
                    (Default)
                </option>
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
            <div id="card-element"></div>
        @endif

        <button type="submit" id="submit-button">
            Subscribe
            <i class="fa fa-spinner fa-spin loading-spinner" id="loading-spinner"></i>
        </button>
        <a id="submit-button" href="{{ route('payment-methods.index') }}">Manage Payment Methods</a>
    </form>


    @if($price && $price->name =='Standard' && $endTime)
        <a id="submit-button" href="{{ route('standard') }}">Page 1</a>
    @elseif($price && $price->name== 'Premium'&& $endTime)
        <a id="submit-button" href="{{ route('standard') }}">Page 1</a>
        <a id="submit-button" href="{{ route('premium') }}">Page 2</a>
    @elseif($price && $price->name=='Elite'&& $endTime)
        <a id="submit-button" href="{{ route('standard') }}">Page 1</a>
        <a id="submit-button" href="{{ route('premium') }}">Page 2</a>
        <a id="submit-button" href="{{ route('elite') }}">Page 3</a>
    @endif
</div>
<a id="submit-button" href="{{ route('dashboard') }}">Dashboard</a>

<script>
    document.addEventListener('DOMContentLoaded', async () => {
        @if (!$defaultPaymentMethod)
        const stripe = Stripe('{{ env('STRIPE_KEY') }}');
        const elements = stripe.elements(); // Retrieve the stripe element to get the Card
        const cardElement = elements.create('card');
        cardElement.mount('#card-element');
        @endif

        const form = document.getElementById('subscription-form');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            // Prevent the default form submission behavior

            const submitButton = document.getElementById('submit-button');
            const loadingSpinner = document.getElementById('loading-spinner');
            submitButton.disabled = true;
            loadingSpinner.style.display = 'inline-block';

            try {
                let paymentMethod;
                @if (!$defaultPaymentMethod)
                const {setupIntent, error} = await stripe.confirmCardSetup(
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

                const planSelect = document.getElementById('plan');
                const plan = planSelect.value;
                const planName = planSelect.options[planSelect.selectedIndex].getAttribute('data-plan-name');

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

                const result = await response.json();


                if (result.error) {
                    document.getElementById('error-message').innerText = result.error;
                } else {

                    const successMessageDiv = document.getElementById('success-message');
                    successMessageDiv.textContent = `You have successfully subscribed to the ${planName} plan!`;
                    successMessageDiv.style.display = 'block';
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            } catch (error) {
                console.error('Unexpected error:', error);
            } finally {
                // Hide loading spinner and enable button
                submitButton.disabled = false;
                loadingSpinner.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>
