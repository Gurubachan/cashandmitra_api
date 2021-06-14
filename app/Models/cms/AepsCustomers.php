<?php

namespace App\Models\cms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AepsCustomers extends Model
{
    use HasFactory;

    protected $table="tbl_aeps_customers";
    protected $fillable=['name','contact','pinCode','merchantId','rbpCustomerId'];
}
