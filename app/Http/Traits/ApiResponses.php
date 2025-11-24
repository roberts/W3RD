<?php

namespace App\Http\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponses
{
    /**
     * Return a standardized error response.
     *
     * Format: {"message": "...", "error_code": "...", "errors": {...}}
     *
     * @param  array<string, mixed>|null  $errors
     */
    protected function errorResponse(
        string $message,
        int $status = 400,
        ?string $errorCode = null,
        ?array $errors = null
    ): JsonResponse {
        $response = ['message' => $message];

        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a Laravel Resource or ResourceCollection with proper wrapping.
     *
     * Format: {"data": {...}} or {"data": [...]}
     * With message: {"data": {...}, "message": "..."}
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
     * Return a paginated collection with data, links, and meta.
     *
     * Format: {"data": [...], "links": {...}, "meta": {...}}
     *
     * @template TKey of int|string
     * @template TValue
     * @param  LengthAwarePaginator<TKey, TValue>  $paginator
     */
    protected function collectionResponse(
        LengthAwarePaginator $paginator,
        callable $resourceClass,
        ?string $message = null
    ): JsonResponse {
        $response = [
            'data' => $resourceClass($paginator->items()),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'path' => $paginator->path(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response);
    }

    /**
     * Return raw data wrapped in standard format.
     *
     * Format: {"data": {...}} or {"data": [...]}
     * With message: {"data": {...}, "message": "..."}
     */
    protected function dataResponse(
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
     * Return only a success message without data.
     *
     * Format: {"message": "..."}
     */
    protected function messageResponse(
        string $message,
        int $status = 200
    ): JsonResponse {
        return response()->json(['message' => $message], $status);
    }

    /**
     * Return auth token response (special case - flat structure).
     *
     * Format: {"token": "...", "user": {...}}
     */
    protected function tokenResponse(
        string $token,
        JsonResource $user
    ): JsonResponse {
        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Return a standardized 404 not found response.
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404, 'NOT_FOUND');
    }

    /**
     * Return a standardized 403 forbidden response.
     */
    protected function forbiddenResponse(string $message = 'This action is unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 403, 'FORBIDDEN');
    }

    /**
     * Return a standardized 401 unauthorized response.
     */
    protected function unauthorizedResponse(string $message = 'Unauthenticated'): JsonResponse
    {
        return $this->errorResponse($message, 401, 'UNAUTHORIZED');
    }

    /**
     * Return a standardized 422 validation error response.
     *
     * @param  array<string, mixed>  $errors
     */
    protected function validationErrorResponse(string $message, array $errors): JsonResponse
    {
        return $this->errorResponse($message, 422, 'VALIDATION_ERROR', $errors);
    }

    /**
     * Return a no content response.
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return a created response with data.
     *
     * Format: {"data": {...}, "message": "..."}
     */
    protected function createdResponse(mixed $data, ?string $message = null): JsonResponse
    {
        return $this->dataResponse($data, $message, 201);
    }

    /**
     * Return a created response with a resource.
     *
     * Format: {"data": {...}, "message": "..."}
     */
    protected function createdResourceResponse(
        JsonResource $resource,
        ?string $message = null
    ): JsonResponse {
        return $this->resourceResponse($resource, $message, 201);
    }

    /**
     * Execute a service call with standardized error handling.
     *
     * @param  callable  $callback  The service call to execute
     * @param  string  $errorMessage  The base error message to use if the call fails
     * @param  int  $status  The HTTP status code to return on error
     * @return mixed The result of the callback
     *
     * @throws \Exception When the service call fails, wrapped with context
     */
    protected function handleServiceCall(
        callable $callback,
        string $errorMessage = 'Operation failed',
        int $status = 500
    ): mixed {
        try {
            return $callback();
        } catch (\App\Exceptions\RematchNotAvailableException $e) {
            // Re-throw custom exceptions to let the exception handler deal with them
            throw $e;
        } catch (\App\Exceptions\InvalidActionDataException $e) {
            // Re-throw action data validation exceptions
            throw $e;
        } catch (\Exception $e) {
            // You can add logging here if needed
            // Log::error($errorMessage, ['exception' => $e->getMessage()]);

            return $this->errorResponse(
                $errorMessage.': '.$e->getMessage(),
                $status,
                'SERVICE_ERROR'
            );
        }
    }
}
