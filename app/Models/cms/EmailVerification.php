<?php

namespace App\Models\cms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed|string id
 * @property mixed emailId
 * @property int|mixed otp
 * @property false|int|mixed expiryTime
 */
class EmailVerification extends Model
{
    use HasFactory;
    protected $table="tbl_email_verification";
}
