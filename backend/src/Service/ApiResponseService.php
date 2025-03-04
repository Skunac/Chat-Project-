<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseService
{
    /**
     * Create a success response
     *
     * @param mixed $data The response data
     * @param int $statusCode HTTP status code
     * @param string|null $message Optional success message
     * @return JsonResponse
     */
    public function success(mixed $data = null, int $statusCode = Response::HTTP_OK, ?string $message = null): JsonResponse
    {
        $response = [
            'error' => false,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return new JsonResponse($response, $statusCode);
    }

    /**
     * Create an error response
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param mixed $errors Additional error details
     * @return JsonResponse
     */
    public function error(string $message, int $statusCode = Response::HTTP_BAD_REQUEST, mixed $errors = null): JsonResponse
    {
        $response = [
            'error' => true,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return new JsonResponse($response, $statusCode);
    }

    /**
     * Create a validation error response
     *
     * @param array $validationErrors Validation error details
     * @param string $message Error message
     * @return JsonResponse
     */
    public function validationError(array $validationErrors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, Response::HTTP_BAD_REQUEST, $validationErrors);
    }

    /**
     * Create a not found response
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    public function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Create an unauthorized response
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    public function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Create a forbidden response
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    public function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Create a conflict response
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    public function conflict(string $message = 'Conflict'): JsonResponse
    {
        return $this->error($message, Response::HTTP_CONFLICT);
    }

    /**
     * Create a server error response
     *
     * @param string $message Error message
     * @param \Throwable|null $exception Exception that caused the error
     * @param bool $includeTrace Whether to include stack trace (only in dev)
     * @return JsonResponse
     */
    public function serverError(
        string $message = 'Internal server error',
        ?\Throwable $exception = null,
        bool $includeTrace = false
    ): JsonResponse {
        $response = [
            'error' => true,
            'message' => $message,
        ];

        if ($exception !== null) {
            $response['exception'] = $exception->getMessage();

            if ($includeTrace) {
                $response['trace'] = $exception->getTraceAsString();
            }
        }

        return new JsonResponse($response, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
