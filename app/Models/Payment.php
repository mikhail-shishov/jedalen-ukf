<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'status_id',
        'method_id',
        'amount',
        'balance_before',
        'balance_after',
        'external_transaction_id',
        'error_message'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
