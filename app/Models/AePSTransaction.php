<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AePSTransaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'shop',
        'service_tax',
        'total_fee',
        'stan',
        'tid',
        'client_ref_id',
        'customer_id',
        'merchant_code',
        'merchant_name',
        'customer_balance',
        'sender_name',
        'auth_code',
        'bank_ref_num',
        'terminal_id',
        'amount',
        'tx_status',
        'trasaction_date',
        'aadhar',
        'response_type_id',
        'reason',
        'comment',
        'message',
        'status',
    ];
}
