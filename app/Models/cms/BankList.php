<?php

namespace App\Models\cms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankList extends Model
{
    use HasFactory;
    protected $table="tbl_bankList";
    protected $fillable=['bankIin','bankName'];
}
