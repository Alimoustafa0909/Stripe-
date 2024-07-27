<!-- resources/views/payment-methods/index.blade.php -->

<!DOCTYPE html>
<html>
<head>
    <title>Payment Methods</title>
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table, th, td {
            border: 1px solid #ccc;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }

        .success-message {
            color: green;
            margin-bottom: 20px;
        }

        .error-message {
            color: red;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<h1>Payment Methods</h1>

@if (session('success'))
    <div class="success-message">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="error-message">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<table>
    <thead>
    <tr>
        <th>Payment Method</th>
        <th>Type</th>
        <th>Default</th>

    </tr>
    </thead>
    <tbody>
    @foreach ($paymentMethods as $paymentMethod)
        <tr>
            <td>****************{{ $paymentMethod->pm_last_four }}</td>
            <td>
                @if (strtolower($paymentMethod->pm_type) == 'visa')
                    <img class="img-fluid" src="https://img.icons8.com/color/48/000000/visa.png" alt="Visa"/>
                @elseif (strtolower($paymentMethod->pm_type) == 'mastercard')
                    <img class="img-fluid" src="https://img.icons8.com/color/48/000000/mastercard-logo.png" alt="MasterCard"/>
                @elseif (strtolower($paymentMethod->pm_type) == 'american express')
                    <img class="img-fluid" src="https://img.icons8.com/color/48/000000/amex.png" alt="American Express"/>
                @elseif (strtolower($paymentMethod->pm_type) == 'unionpay')
                    <img class="img-fluid" src="https://img.icons8.com/color/48/000000/unionpay.png" alt="UnionPay"/>
                @else
                    <img class="img-fluid" src="https://img.icons8.com/color/48/000000/default.png" alt="Default"/>
                @endif
                {{ ucfirst($paymentMethod->pm_type) }}
            </td>
            <td>
                {{ $paymentMethod->default ? 'Default' : '' }}

                @if (!$paymentMethod->default)
                    <form action="{{ route('payment-methods.set-default') }}" method="POST">
                        @csrf
                        <input type="hidden" name="payment_method_id" value="{{ $paymentMethod->id }}">
                        <button type="submit">Set as Default</button>
                    </form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody></table>

<h2>Add Payment Method</h2>
<form id="payment-method-form">
    <div id="card-element"><!-- Stripe Element will be inserted here --></div>
    <button type="submit">Add Payment Method</button>
</form>

<script>
    document.addEventListener('DOMContentLoaded', async () => {
        const stripe = Stripe('{{ env('STRIPE_KEY') }}');
        const elements = stripe.elements();
        const cardElement = elements.create('card');
        cardElement.mount('#card-element');

        const form = document.getElementById('payment-method-form');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const {setupIntent, error} = await stripe.confirmCardSetup(
                '{{ $clientSecret }}', {
                    payment_method: {
                        card: cardElement,
                    }
                }
            );

            if (error) {
                console.error('Error setting up card:', error);
            } else {
                const response = await fetch('{{ route('payment-methods.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        payment_method: setupIntent.payment_method
                    }),
                });

                const result = await response.json();

                if (result.success) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);

                } else {
                    console.error('Error adding payment method:', result.error);
                }
            }
        });
    });
</script>
<a href="{{ route('subscription') }}">Go Back</a>
</body>
</html>
