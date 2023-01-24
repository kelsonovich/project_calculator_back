<?php

namespace App\Traits\Controller;

use Illuminate\Http\JsonResponse;

trait ResponseTrait
{
    protected function onSuccess (mixed $data, string $message = '', int $code = 200): JsonResponse
    {
        return response()->json(
            [
                'status'  => true,
                'message' => (strlen($message) > 0) ? $message : null,
                'result'  => $data,
                'errors'  => null,
            ],
            $code
        );
    }

    protected function onError (mixed $errors, string $message, int $code): JsonResponse
    {
        return response()->json(
            [
                'status'  => false,
                'message' => $message,
                'result'  => null,
                'errors'  => $errors,
            ],
            $code
        );
    }
}
