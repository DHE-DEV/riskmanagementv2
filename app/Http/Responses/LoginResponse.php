<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $customer = Auth::guard('customer')->user();

        if ($customer && !$customer->hasVerifiedEmail()) {
            Auth::guard('customer')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Bitte bestätigen Sie zuerst Ihre E-Mail-Adresse, bevor Sie sich einloggen.',
            ]);
        }

        return redirect()->intended(config('fortify.home'));
    }
}
