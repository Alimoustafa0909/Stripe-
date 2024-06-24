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
        <th>Default</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($paymentMethods as $paymentMethod)
        <tr>
            <td>{{ $paymentMethod->stripe_payment_method_id }}</td>
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
    </tbody>
</table>

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

            const {paymentMethod, error} = await stripe.createPaymentMethod('card', cardElement);

            if (error) {
                console.error('Error creating payment method:', error);
            } else {
                const response = await fetch('{{ route('payment-methods.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({payment_method: paymentMethod.id}),
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = '{{ route('payment-methods.index') }}';
                } else {
                    console.error('Error adding payment method:', result.error);
                }
            }
        });
    });
</script>
<a href="{{route('subscription')}}">Go Back</a>


</body>
</html>
