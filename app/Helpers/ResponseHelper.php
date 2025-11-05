<?php

use Illuminate\Http\JsonResponse;

if (!function_exists('successResponse')) {
    function successResponse(mixed $data = null, $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }
}

if (!function_exists('errorResponse')) {
    function errorResponse(mixed $data = null, $message = 'Error', $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }
}
