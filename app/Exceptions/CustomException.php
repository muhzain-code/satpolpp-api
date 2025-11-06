<?php

namespace App\Exceptions;

use Exception;

class CustomException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'status' => 'Error',
            'message' => $this->getMessage()
        ], $this->getCode() ?: 400);
    }
}
