<?php

namespace App\Models\cms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed type
 * @property mixed isActive
 */
class BusinessType extends Model
{
    use HasFactory;

    protected $table="tbl_business_type";
}
