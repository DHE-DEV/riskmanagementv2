<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ConfirmPasswordController extends Controller
{
    public function store(Request $request)
    {
        $customer = auth('customer')->user();

        // OAuth-User (Google etc.) haben kein lokales Passwort
        // Für diese User bestätigen wir automatisch
        if ($customer->provider && !$customer->password) {
            $request->session()->put('auth.password_confirmed_at', time());

            return response()->json(['confirmed' => true], 201);
        }

        if (!Hash::check($request->password, $customer->password)) {
            return response()->json([
                'message' => 'Das Passwort ist nicht korrekt.',
            ], 422);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return response()->json(['confirmed' => true], 201);
    }
}
