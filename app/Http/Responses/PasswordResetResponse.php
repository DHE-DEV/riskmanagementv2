<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;

class PasswordResetResponse implements PasswordResetResponseContract
{
    protected string $status;

    public function __construct(string $status)
    {
        $this->status = $status;
    }

    public function toResponse($request)
    {
        return $request->wantsJson()
            ? new JsonResponse(['message' => trans($this->status)], 200)
            : redirect()->route('customer.login')->with('status', trans($this->status));
    }
}
