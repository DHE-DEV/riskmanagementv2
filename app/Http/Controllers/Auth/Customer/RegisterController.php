<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Display the registration view
     */
    public function create(): View
    {
        return view('auth.customer.register');
    }
}
