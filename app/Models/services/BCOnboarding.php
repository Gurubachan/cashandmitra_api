<?php

namespace App\Models\services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed userId
 * @property mixed serviceId
 * @property mixed providerid
 * @property false|mixed|string requested_data
 * @property bool|mixed|string response_data
 */
class BCOnboarding extends Model
{
    use HasFactory;
    protected $table="tbl_bconboarding";
    protected $casts=['requested_data'=>'array','response_data'=>'array'];
}
