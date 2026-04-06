<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\PasswordResetResponse;
use App\Http\Responses\RegisterResponse;
use App\Models\Customer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ignore Fortify's default routes, we'll customize them
        Fortify::ignoreRoutes();

        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(PasswordResetResponseContract::class, PasswordResetResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Custom authentication with MD5 legacy password fallback
        Fortify::authenticateUsing(function (Request $request) {
            $customer = Customer::where('email', $request->input('email'))->first();

            if (! $customer) {
                return null;
            }

            // Try standard bcrypt password first
            if (Hash::check($request->input('password'), $customer->password)) {
                // Clear legacy hash if still present
                if ($customer->legacy_password_md5) {
                    $customer->update(['legacy_password_md5' => null]);
                }
                return $customer;
            }

            // Fallback: try MD5 legacy password
            if ($customer->legacy_password_md5) {
                $md5Input = md5($request->input('password'));
                if (hash_equals($customer->legacy_password_md5, $md5Input)) {
                    // Migrate password to bcrypt
                    $customer->update([
                        'password' => Hash::make($request->input('password')),
                        'legacy_password_md5' => null,
                    ]);
                    return $customer;
                }
            }

            return null;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // Define custom views for customer authentication
        Fortify::loginView(function () {
            return view('customer.auth.login');
        });

        Fortify::registerView(function () {
            return view('customer.auth.register');
        });

        Fortify::requestPasswordResetLinkView(function () {
            return view('customer.auth.forgot-password');
        });

        Fortify::resetPasswordView(function ($request) {
            return view('customer.auth.reset-password', [
                'token' => $request->route('token'),
                'email' => $request->query('email'),
            ]);
        });

        Fortify::verifyEmailView(function () {
            return view('customer.auth.verify-email');
        });

        Fortify::twoFactorChallengeView(function () {
            return view('customer.auth.two-factor-challenge');
        });

        Fortify::confirmPasswordView(function () {
            return view('customer.auth.confirm-password');
        });
    }
}
