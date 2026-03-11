<?php

namespace Database\Factories;

use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of \App\Models\WorkflowStep
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class WorkflowStepFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = WorkflowStep::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
        ];
    }
}
