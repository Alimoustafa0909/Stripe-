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

@if($price && $endTime )
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
                <input type="radio" id="product-{{ $product->id }}" name="product" value="{{ $product->id }}" data-product-id="{{ $product->id }}">
                <label for="product-{{ $product->id }}">Select {{ $product->name }}</label>
                <select id="plan-{{ $product->id }}" name="plan-{{ $product->id }}">
                    @foreach($product->plans as $plan)
                        <option value="{{ $plan->stripe_plan_id }}" data-plan-name="{{ $plan->name }}">
                            {{$plan->name}}  {{ $plan->amount / 100 }} {{ strtoupper($plan->currency) }}
                            / {{ $plan->interval_count }}   {{ $plan->interval }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    @endforeach
</div>

<!-- Subscription and Manage Payment Methods buttons -->
<div class="button-container">
    <form id="subscription-form">
        <div id="card-element"></div>
        <button type="submit" id="submit-button">
            Subscribe
            <i class="fa fa-spinner fa-spin loading-spinner" id="loading-spinner"></i>
        </button>
    </form>
    <a id="submit-button" href="{{ route('payment-methods.index') }}">Manage Payment Methods</a>
</div>

<a id="submit-button" href="{{ route('dashboard') }}">Dashboard</a>

<script>
    document.addEventListener('DOMContentLoaded', async () => {
        const stripe = Stripe('{{ env('STRIPE_KEY') }}');
        const elements = stripe.elements(); // Retrieve the stripe element to get the Card
        const cardElement = elements.create('card');
        cardElement.mount('#card-element');

        const form = document.getElementById('subscription-form');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            // Prevent the default form submission behavior

            const submitButton = document.getElementById('submit-button');
            const loadingSpinner = document.getElementById('loading-spinner');
            submitButton.disabled = true;
            loadingSpinner.style.display = 'inline-block';

            const { paymentMethod, error } = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
            });

            if (error) {
                console.error(error);
                alert('An error occurred while creating the payment method. Please try again.');
                submitButton.disabled = false;
                loadingSpinner.style.display = 'none';
                return;
            }

            const selectedProductInput = document.querySelector('input[name="product"]:checked');
            if (!selectedProductInput) {
                alert('Please select a product.');
                submitButton.disabled = false;
                loadingSpinner.style.display = 'none';
                return;
            }

            const productId = selectedProductInput.dataset.productId;
            const planSelect = document.getElementById(`plan-${productId}`);
            const selectedPlan = planSelect.value;

            try {
                const response = await fetch('subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        payment_method: paymentMethod.id,
                        plan: selectedPlan,
                        product: productId,
                    }),
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok.');
                }

                const responseData = await response.json();

                if (responseData.success) {
                    alert(responseData.message);
                    location.reload();
                } else {
                    alert(responseData.message || 'An error occurred while processing your subscription.');
                }
            } catch (error) {
                console.error('Subscription failed:', error);
                alert('An error occurred while processing your subscription. Please try again.');
            } finally {
                submitButton.disabled = false;
                loadingSpinner.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>
