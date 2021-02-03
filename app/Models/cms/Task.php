<?php

namespace App\Models\cms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed taskName
 * @property mixed taskDescription
 * @property mixed taskReminder
 * @property mixed taskAssignTo
 * @property mixed taskAssignedBy
 * @property mixed parentId
 * @property mixed taskStatus
 * @property mixed relatedTo
 */
class Task extends Model
{
    use HasFactory;
    protected $table="tbl_task";

    protected $casts = [
        'relatedTo' => 'array',
    ];
}
