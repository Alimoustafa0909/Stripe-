<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Stripe\SubscriptionItem;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use Billable;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'trial_ends_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'trial_ends_at' => 'datetime:Y-m-d',
        ];
    }


    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function defaultPaymentMethod()
    {
        return $this->hasOne(PaymentMethod::class)->where('default', true);
    }


    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function getFullName()
    {
        return $this->name;
    }

}
