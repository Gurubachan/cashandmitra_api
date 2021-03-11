<?php

namespace App\Models\cms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed name
 * @property mixed contact
 * @property mixed email
 * @property mixed address
 * @property mixed leadSource
 * @property mixed entryBy
 * @property mixed visit_date
 * @property false|mixed|string businessType
 * @property mixed isInterested
 * @property mixed pinCodeId
 * @property integer id
 * @property mixed interestedIn
 * @property mixed dealSize
 * @property mixed entryLocation
 * @property mixed entryAddress
 */
class Lead extends Model
{
    use HasFactory;
    protected $casts = [
        'entryLocation' => 'array',
    ];

}
