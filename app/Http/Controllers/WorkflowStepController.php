<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkflowStepRequest;
use App\Http\Requests\UpdateWorkflowStepRequest;
use App\Models\Auth\WorkflowStep;

class WorkflowStepController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWorkflowStepRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(WorkflowStep $workflowStep)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWorkflowStepRequest $request, WorkflowStep $workflowStep)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkflowStep $workflowStep)
    {
        //
    }
}
