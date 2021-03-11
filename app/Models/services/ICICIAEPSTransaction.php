<?php

namespace App\Models\services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int|mixed serviceId
 * @property mixed txnTime
 * @property mixed bcId
 * @property mixed userId
 * @property mixed stanNo
 * @property mixed amount
 * @property mixed status
 * @property mixed iin
 * @property mixed txnMedium
 * @property mixed mobile
 * @property mixed id
 * @property bool|mixed response
 * @property mixed txnType
 * @property mixed response_at
 * @property mixed|string aadhar
 */
class ICICIAEPSTransaction extends Model
{
    use HasFactory;
    protected $table="tbl_icici_aeps_transaction";
    protected $casts=[
        'response'=>'array',
        'update_response'=>'array',
        'checkAEPSTxnStatus_response'=>'array'
    ];
}
