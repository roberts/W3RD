<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponses
{
    /**
     * Return a standardized error response.
     */
    protected function errorResponse(
        string $message,
        int $status = 400,
        ?string $errorCode = null,
        ?array $context = null
    ): JsonResponse {
        $response = ['message' => $message];

        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }

        if ($context !== null) {
            $response['context'] = $context;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a standardized success response with data.
     */
    protected function successResponse(
        mixed $data,
        ?string $message = null,
        int $status = 200
    ): JsonResponse {
        $response = ['data' => $data];

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a success response with a resource.
     */
    protected function resourceResponse(
        JsonResource|ResourceCollection $resource,
        ?string $message = null,
        int $status = 200
    ): JsonResponse {
        $response = ['data' => $resource];

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a standardized 404 not found response.
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Return a standardized 403 forbidden response.
     */
    protected function forbiddenResponse(string $message = 'This action is unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Return a standardized 401 unauthorized response.
     */
    protected function unauthorizedResponse(string $message = 'Unauthenticated'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Return a no content response.
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return a created response.
     */
    protected function createdResponse(mixed $data, ?string $message = null): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }
}
