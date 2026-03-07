<?php

namespace App\Services;

use Throwable;

class MessageService
{
    /**
     * Return a generic 422 JSON error. Logs the exception when provided (e.g. in catch blocks).
     *
     * @param  Throwable|null  $e
     * @return \Illuminate\Http\JsonResponse
     */
    public function responseError(?Throwable $e = null)
    {
        if ($e !== null) {
            report($e);
        }

        return response([
            'message' => 'An error has occurred, please reload the page or try again later. Please contact the administrator if error has re-occured.',
            'status' => false,
            'status_code' => 422,
        ], 422);
    }
}