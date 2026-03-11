<?php

namespace App\Models;

use Database\Factories\WorkflowStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model
{
    /** @use HasFactory<WorkflowStepFactory> */
    use HasFactory;
}
