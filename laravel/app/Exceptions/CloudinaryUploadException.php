<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class CloudinaryUploadException extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Error processing file upload',
            'error' => $this->getMessage(),
        ], 500);
    }
}
