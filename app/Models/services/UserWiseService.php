<?php

namespace App\Models\services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWiseService extends Model
{
    use HasFactory;
    protected $table="tbl_user_wise_services";
    protected $fillable=[
      'serviceId','onboardStatus','userId','created_at','updated_at', 'isActive'
    ];


}
