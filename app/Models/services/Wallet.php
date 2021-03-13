<?php

namespace App\Models\services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed user_id
 * @property mixed previous_balance
 * @property mixed closing_balance
 * @property mixed transaction_type
 * @property mixed description
 * @property mixed transaction_date
 * @property mixed status
 * @property mixed transaction_reference
 * @property mixed service_id
 * @property mixed transacting_amount
 * @property mixed id
 * @property mixed|string wallet_operation
 */
class Wallet extends Model
{
    use HasFactory;
    protected $table="user_wallet";
}
