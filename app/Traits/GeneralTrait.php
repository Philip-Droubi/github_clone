<?php

namespace App\Traits;

trait GeneralTrait
{
    protected function success($data = [], $message = 'ok', $status = 200)
    {
        return response()->json([
            'success' => true,
            'status' => $status,
            'message' => (string) $message,
            'data' => $data,
        ], $status);
    }

    protected function fail($message, $status = 400)
    {
        return response()->json([
            'success' => false,
            'status' => $status,
            'message' => $message,
        ], $status);
    }
}

// use App\Traits\GeneralTrait; befor the controller class
// use GeneralTrait; inside the controller class
