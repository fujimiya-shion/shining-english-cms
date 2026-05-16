<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait Jsonable
{
    public function success(
        string $message = 'OK',
        mixed $data = null,
        int $statusCode = 200,
        mixed $meta = null
    ): JsonResponse {
        return response()->json([
            'message' => $message,
            'status' => true,
            'status_code' => $statusCode,
            'data' => $data,
            'meta' => $meta,
        ], $statusCode);
    }

    public function error(
        string $message = 'Error',
        int $statusCode = 500,
        mixed $errors = null,
    ): JsonResponse {
        return response()->json([
            'message' => $message,
            'status' => false,
            'status_code' => $statusCode,
            'errors' => $errors,
        ], $statusCode);
    }

    public function notfound(
        string $message = 'Not found',
        mixed $errors = null,
    ): JsonResponse {
        return $this->error($message, 404, $errors);
    }

    public function forbidden(
        string $message = 'Forbidden',
        mixed $errors = null,
    ): JsonResponse {
        return $this->error($message, 403, $errors);
    }

    public function unauthorized(
        string $message = 'Unauthorized',
        mixed $errors = null,
    ): JsonResponse {
        return $this->error($message, 401, $errors);
    }

    public function created(
        mixed $data,
        string $message = 'Created',
    ): JsonResponse {
        return $this->success($message, $data, 201);
    }

    public function deleted(
        string $message = 'Deleted',
    ): JsonResponse {
        return $this->success($message, statusCode: 200);
    }
}
