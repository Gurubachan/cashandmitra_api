<?php

namespace App\Models\services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property mixed serviceId
 * @property mixed|string txnType
 * @property Carbon|mixed txnDate
 * @property mixed merchantId
 * @property mixed userId
 * @property mixed bankIin
 * @property mixed amount
 * @property mixed txnMedium
 * @property mixed|string route
 * @property array|mixed remoteDetails
 * @property array|mixed requestData
 * @property mixed customerId
 * @property mixed|string status
 * @property mixed id
 * @property mixed aadhaarNo
 */
class RbpAepsTransaction extends Model
{
    use HasFactory;
    protected $table="tbl_rbp_aeps";
    protected $casts=[
        'requestData'=>'array',
        'responseData'=>'array',
        'remoteDetails'=>'array'
    ];
}
