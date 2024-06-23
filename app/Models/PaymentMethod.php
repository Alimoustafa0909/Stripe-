<?php

// app/Models/PaymentMethod.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'user_id',
        'stripe_payment_method_id',
        'pm_type',
        'pm_last_four',
        'expires_at',
        'default'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
