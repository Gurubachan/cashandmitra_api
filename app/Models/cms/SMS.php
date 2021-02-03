<?php

namespace App\Models\cms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed|string id
 * @property mixed contactnumber
 * @property int|mixed otp
 * @property bool|mixed isdelivered
 * @property false|mixed|string experytime
 */
class SMS extends Model
{
    use HasFactory;
    protected $table="tbl_otp_verification";
}
