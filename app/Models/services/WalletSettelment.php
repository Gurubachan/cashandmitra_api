<?php

namespace App\Models\services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed user_id
 * @property mixed service_id
 * @property mixed txnType
 * @property mixed txnTime
 * @property mixed amount
 * @property mixed bankName
 * @property mixed ifsc
 * @property mixed txnMedium
 * @property mixed bene_account
 * @property mixed id
 * @property bool|mixed status
 * @property mixed isWalletUpdate
 * @property mixed walletReferenceNo
 */
class WalletSettelment extends Model
{
    use HasFactory;

    protected $table="tbl_wallet_settlement";

    protected $casts=['response'=>'array', 'update_response'=>'array'];
}
