<?php

namespace App\Models\services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayoutBankResponse extends Model
{
    use HasFactory;
    protected $table="tbl_open_payout_transaction_status_message";
}
