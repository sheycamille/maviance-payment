<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

     protected $fillable = [
        'currency',
        'customer_name',
        'description',
        'email',
        'phone_number',
        'lang_key',
        'merchant_reference_id',
        'order_transaction_id',
        'optRefOne',
        'optRefTwo',
        'expiry_date',
        'order_date',
        'total_amount',
        'id',
        'items',
        'redirect_url',
        'status',
        'payment_provider',
    ];

    protected $casts = [
        'id' => 'array',
        'items' => 'array',
        'expiry_date' => 'datetime',
        'order_date' => 'datetime',
    ];
}
