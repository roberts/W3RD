<?php

namespace App\Models\Auth;

use Database\Factories\Auth\WorkflowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    /** @use HasFactory<WorkflowFactory> */
    use HasFactory;
}
