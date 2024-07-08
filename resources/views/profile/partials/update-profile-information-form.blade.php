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
        .product-container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center; /* Center products horizontally */
            margin-top: 20px; /* Adjust as needed */
        }

        .card {
            width: 800px;
            margin: 0 auto; /* Center the card element */
            padding: 40px;
            border: 1px solid #ccc;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            flex: 1 1 300px;
        }

        .subscription-form {
            margin-top: 20px;
        }

        .button-container {
            display: flex;
            justify-content: center; /* Center buttons horizontally */
            margin-top: 20px; /* Adjust as needed */
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
            margin-top: 20px;
            margin-right: 50px;
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
                @if(!$user->onTrial())
                    <th>Name</th>
                    <th>Amount</th>
                    <th>Currency</th>
                    <th>Interval</th>
                    <th>Ends At</th>
                @endif
                <th>Trialing Until</th>
            </tr>
            <tr>
                @if(!$user->onTrial())
                    <td>{{ $price->name }}</td>
                    <td>{{ $price->amount / 100 }}$</td>
                    <td>{{ strtoupper($price->currency) }}</td>
                    <td>{{ $price->interval_count }} {{ $price->interval }}</td>
                    <td>{{ $user->subscription($productId)->ends_at ? ($user->subscription($productId)->ends_at) : 'N/A' }}</td>
                @endif
                <td>
                    @if($user->trial_ends_at || ($user->subscription($productId) && ($user->subscription($productId)->stripe_status=='trialing')))
                        {{ $user->trial_ends_at ?? $user->subscription($productId)->trial_ends_at }}
                    @else
                        N/A
                    @endif
                </td>
            </tr>
        </table>
        <form id="cancel-subscription-form" action="{{ route('cancel_subscription') }}" method="POST">
            @csrf
            <input type="hidden" name="_method">
            @if($user->trial_ends_at)
                <h2>You are on Trial Mode Now Enjoy it </h2>
            @elseif(!$user->subscription($productId)->ends_at)
                <button type="submit" class="cancel-button">Cancel Subscription</button>
            @endif
        </form>
    </div>
@endif

<div class="product-container">
    @foreach($products as $product)
        <div class="card">
            <h2>Product: {{ $product->name }}</h2>
            <p>Description: {{ $product->description }}</p>
            <div>
                <input type="radio" id="product-{{ $product->id }}" name="product" value="{{ $product->id }}"
                       data-product-id="{{ $product->id }}">
                <label for="product-{{ $product->id }}">Select {{ $product->name }}</label>
            </div>
        </div>
    @endforeach
</div>

<!-- Form for subscription and manage payment methods -->
<form id="subscription-form">
    <div class="subscription-form">
        <label for="plan">Select Plan:</label>
        <select id="plan" name="plan">
            <!-- Options will be dynamically populated based on selected product -->
        </select>
    </div>

    @if ($defaultPaymentMethod)
        <div class="subscription-form">
            <label for="payment-method">Select Payment Method:</label>
            <select id="payment-method" name="payment_method">
                <option value="{{ $defaultPaymentMethod->stripe_payment_method_id }}">
                    {{ $defaultPaymentMethod->pm_type }} ending in {{ $defaultPaymentMethod->pm_last_four }} (Default)
                </option>
                @foreach ($paymentMethods as $paymentMethod)
                    @if (!$paymentMethod->default)
                        <option value="{{ $paymentMethod->stripe_payment_method_id }}">
                            {{ $paymentMethod->pm_type }} ending in {{ $paymentMethod->pm_last_four }}
                        </option>
                    @endif
                @endforeach
            </select>
        </div>
    @else
        <!-- Placeholder for the stripe Card Element -->
        <div id="card-element"></div>
    @endif

    <div class="button-container">
        <button type="submit" id="submit-button">
            Subscribe
            <i class="fa fa-spinner fa-spin loading-spinner" id="loading-spinner"></i>
        </button>
    </div>
</form>

<div class="button-container">
    <a id="submit-button" href="{{ route('payment-methods.index') }}">Manage Payment Methods</a>
    <a id="submit-button" href="{{ route('dashboard') }}">Dashboard</a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', async () => {
        @if (!$defaultPaymentMethod)
        const stripe = Stripe('{{ env('STRIPE_KEY') }}');
        const elements = stripe.elements();
        const cardElement = elements.create('card');
        cardElement.mount('#card-element');
        @endif
        const productRadios = document.querySelectorAll('input[name="product"]');
        const planSelect = document.getElementById('plan');

        productRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                const selectedProductId = radio.value;
                planSelect.innerHTML = '';

                const selectedProduct = @json($products).
                find(product => product.id == selectedProductId);
                selectedProduct.plans.forEach(plan => {
                    const option = document.createElement('option');
                    option.value = plan.id;
                    option.textContent = `${plan.name} - $${(plan.amount / 100).toFixed(2)} per ${plan.interval}`;
                    planSelect.appendChild(option);
                });
            });
        });

        const form = document.getElementById('subscription-form');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

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
                    document.getElementById('error-message').innerText = error.message;
                    document.getElementById('error-message').style.display = 'block';
                    return;
                }

                paymentMethod = setupIntent.payment_method;
                @else
                    paymentMethod = document.getElementById('payment-method').value;
                @endif

                const plan = planSelect.value;

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
                    document.getElementById('error-message').style.display = 'block';
                } else {
                    const successMessageDiv = document.getElementById('success-message');
                    successMessageDiv.textContent = result.message;
                    successMessageDiv.style.display = 'block';
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            } catch (error) {
                console.error('Unexpected error:', error);
            } finally {
                submitButton.disabled = false;
                loadingSpinner.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>
