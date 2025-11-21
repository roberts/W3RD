<?php

namespace App\Http\Controllers\Api\V1\Economy;

use App\DataTransferObjects\Economy\PlanData;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;

class PlanController extends Controller
{
    use ApiResponses;

    /**
     * Get available subscription plans.
     */
    public function index(Request $request): JsonResponse
    {
        // In production, these would come from Stripe
        $plans = [
            [
                'id' => 'free',
                'name' => 'Free',
                'price' => 0,
                'currency' => 'usd',
                'interval' => 'month',
                'features' => [
                    'Limited games per day',
                    'Basic game modes',
                    'Community support',
                ],
            ],
            [
                'id' => 'pro',
                'name' => 'Pro',
                'price' => 999,
                'currency' => 'usd',
                'interval' => 'month',
                'features' => [
                    'Unlimited games',
                    'All game modes',
                    'Priority matchmaking',
                    'Custom avatars',
                    'Priority support',
                ],
            ],
            [
                'id' => 'elite',
                'name' => 'Elite',
                'price' => 1999,
                'currency' => 'usd',
                'interval' => 'month',
                'features' => [
                    'Everything in Pro',
                    'Tournament access',
                    'Exclusive game modes',
                    'Early access to new features',
                    'Private lobbies',
                    'VIP support',
                ],
            ],
        ];

        return $this->dataResponse([
            'plans' => array_map(fn ($plan) => PlanData::from($plan), $plans),
        ]);
    }
}
