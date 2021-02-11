<?php

namespace App\Models\cms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed inTime
 * @property mixed coords
 * @property mixed location
 * @property mixed userId
 */
class UserAttendance extends Model
{
    use HasFactory;
    protected $table="tbl_user_attendance";
    protected $casts=[
        'coords'=>'array'
    ];
}
