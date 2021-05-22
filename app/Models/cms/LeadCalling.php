<?php

namespace App\Models\cms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed leadId
 * @property mixed assignTo
 * @property mixed assignBy
 * @property false|mixed|string callingOn
 */
class LeadCalling extends Model
{
    use HasFactory;
    protected $table="tbl_lead_calling";
    protected $fillable = ['leadId','assignTo', 'assignBy','callingDate','created_at','updated_at'];

}
