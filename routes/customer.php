<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;
use Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\ProfileInformationController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;

Route::prefix('customer')->name('customer.')->group(function () {
    $enableViews = config('fortify.views', true);

    // Customer Dashboard Route
    Route::middleware(['auth:'.config('fortify.guard')])->group(function () {
        Route::get('/dashboard', function () {
            $customer = auth('customer')->user();
            $passolutionService = app(\App\Services\PassolutionService::class);

            // Update subscription if Passolution is active and data is stale
            if ($customer->hasActivePassolution() && $passolutionService->needsUpdate($customer)) {
                $passolutionService->updateSubscription($customer);
                // Refresh customer data after update
                $customer->refresh();
            }

            return view('customer.dashboard');
        })->name('dashboard');

        Route::post('/profile/customer-type', [\App\Http\Controllers\Customer\ProfileController::class, 'updateCustomerType'])
            ->name('profile.update-customer-type');

        Route::post('/profile/business-type', [\App\Http\Controllers\Customer\ProfileController::class, 'updateBusinessType'])
            ->name('profile.update-business-type');

        Route::post('/profile/company-address', [\App\Http\Controllers\Customer\ProfileController::class, 'updateCompanyAddress'])
            ->name('profile.update-company-address');

        Route::post('/profile/billing-address', [\App\Http\Controllers\Customer\ProfileController::class, 'updateBillingAddress'])
            ->name('profile.update-billing-address');

        Route::get('/profile/countries', [\App\Http\Controllers\Customer\ProfileController::class, 'getCountries'])
            ->name('profile.get-countries');

        Route::post('/profile/hide-profile-completion', [\App\Http\Controllers\Customer\ProfileController::class, 'toggleHideProfileCompletion'])
            ->name('profile.hide-profile-completion');

        Route::post('/profile/toggle-directory-listing', [\App\Http\Controllers\Customer\ProfileController::class, 'toggleDirectoryListing'])
            ->name('profile.toggle-directory-listing');

        Route::post('/profile/toggle-branch-management', [\App\Http\Controllers\Customer\ProfileController::class, 'toggleBranchManagement'])
            ->name('profile.toggle-branch-management');

        // Passolution OAuth routes
        Route::get('/passolution/authorize', [\App\Http\Controllers\Customer\PassolutionOAuthController::class, 'redirect'])
            ->name('passolution.authorize');

        Route::get('/passolution/callback', [\App\Http\Controllers\Customer\PassolutionOAuthController::class, 'callback'])
            ->name('passolution.callback');

        Route::post('/passolution/disconnect', [\App\Http\Controllers\Customer\PassolutionOAuthController::class, 'disconnect'])
            ->name('passolution.disconnect');

        Route::post('/passolution/refresh-token', [\App\Http\Controllers\Customer\PassolutionOAuthController::class, 'refreshToken'])
            ->name('passolution.refresh-token');

        // Branch Management routes
        Route::prefix('branches')->name('branches.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\BranchController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Customer\BranchController::class, 'store'])->name('store');
            Route::post('/import', [\App\Http\Controllers\Customer\BranchController::class, 'import'])->name('import');
            Route::get('/export-status', [\App\Http\Controllers\Customer\BranchController::class, 'exportStatus'])->name('export-status');
            Route::post('/export', [\App\Http\Controllers\Customer\BranchController::class, 'export'])->name('export');
            Route::get('/download/{filename}', [\App\Http\Controllers\Customer\BranchController::class, 'download'])->name('download');
            Route::put('/{branch}', [\App\Http\Controllers\Customer\BranchController::class, 'update'])->name('update');
            Route::delete('/{branch}', [\App\Http\Controllers\Customer\BranchController::class, 'destroy'])->name('destroy');
            Route::post('/{branch}/cancel-deletion', [\App\Http\Controllers\Customer\BranchController::class, 'cancelScheduledDeletion'])->name('cancel-deletion');
        });

        // Notification routes
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\NotificationController::class, 'index'])->name('index');
            Route::post('/{id}/read', [\App\Http\Controllers\Customer\NotificationController::class, 'markAsRead'])->name('mark-read');
            Route::post('/mark-all-read', [\App\Http\Controllers\Customer\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
            Route::delete('/{id}', [\App\Http\Controllers\Customer\NotificationController::class, 'delete'])->name('delete');
        });
    });

    // Authentication Routes
    Route::middleware(['guest:'.config('fortify.guard')])->group(function () use ($enableViews) {
        $limiter = config('fortify.limiters.login');

        if ($enableViews) {
            Route::get('/login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');
        }

        Route::post('/login', [AuthenticatedSessionController::class, 'store'])
            ->middleware(array_filter([
                $limiter ? 'throttle:'.$limiter : null,
            ]))->name('login.store');
    });

    // Registration Routes
    if (Features::enabled(Features::registration())) {
        Route::middleware(['guest:'.config('fortify.guard')])->group(function () use ($enableViews) {
            if ($enableViews) {
                Route::get('/register', [RegisteredUserController::class, 'create'])
                    ->name('register');
            }

            Route::post('/register', [RegisteredUserController::class, 'store'])
                ->name('register.store');
        });
    }

    // Password Reset Routes
    if (Features::enabled(Features::resetPasswords())) {
        Route::middleware(['guest:'.config('fortify.guard')])->group(function () use ($enableViews) {
            if ($enableViews) {
                Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
                    ->name('password.request');
            }

            Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
                ->name('password.email');

            if ($enableViews) {
                Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
                    ->name('password.reset');
            }

            Route::post('/reset-password', [NewPasswordController::class, 'store'])
                ->name('password.update');
        });
    }

    // Email Verification Routes
    if (Features::enabled(Features::emailVerification())) {
        Route::middleware(['auth:'.config('fortify.guard')])->group(function () use ($enableViews) {
            if ($enableViews) {
                Route::get('/email/verify', [EmailVerificationPromptController::class, '__invoke'])
                    ->name('verification.notice');
            }

            Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');

            Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('verification.send');
        });
    }

    // Profile Management Routes
    if (Features::enabled(Features::updateProfileInformation())) {
        Route::middleware(['auth:'.config('fortify.guard')])->group(function () {
            Route::put('/user/profile-information', [ProfileInformationController::class, 'update'])
                ->name('user-profile-information.update');
        });
    }

    // Password Update Routes
    if (Features::enabled(Features::updatePasswords())) {
        Route::middleware(['auth:'.config('fortify.guard')])->group(function () {
            Route::put('/user/password', [PasswordController::class, 'update'])
                ->name('user-password.update');
        });
    }

    // Password Confirmation Routes
    Route::middleware(['auth:'.config('fortify.guard')])->group(function () use ($enableViews) {
        if ($enableViews) {
            Route::get('/user/confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');
        }

        Route::post('/user/confirm-password', [ConfirmablePasswordController::class, 'store'])
            ->name('password.confirm.store');

        Route::get('/user/confirmed-password-status', [ConfirmedPasswordStatusController::class, 'show'])
            ->middleware(['password.confirm'])
            ->name('password.confirmation');
    });

    // Two-Factor Authentication Routes
    if (Features::enabled(Features::twoFactorAuthentication())) {
        Route::middleware(['guest:'.config('fortify.guard')])->group(function () use ($enableViews) {
            $limiter = config('fortify.limiters.two-factor');

            if ($enableViews) {
                Route::get('/two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'create'])
                    ->name('two-factor.login');
            }

            Route::post('/two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'store'])
                ->middleware(array_filter([
                    $limiter ? 'throttle:'.$limiter : null,
                ]))->name('two-factor.login.store');
        });

        Route::middleware(['auth:'.config('fortify.guard')])->group(function () {
            Route::post('/user/two-factor-authentication', [TwoFactorAuthenticationController::class, 'store'])
                ->middleware(['password.confirm'])
                ->name('two-factor.enable');

            Route::post('/user/confirmed-two-factor-authentication', [TwoFactorAuthenticationController::class, 'update'])
                ->middleware(['password.confirm'])
                ->name('two-factor.confirm');

            Route::delete('/user/two-factor-authentication', [TwoFactorAuthenticationController::class, 'destroy'])
                ->middleware(['password.confirm'])
                ->name('two-factor.disable');

            Route::get('/user/two-factor-qr-code', [TwoFactorQrCodeController::class, 'show'])
                ->middleware(['password.confirm'])
                ->name('two-factor.qr-code');

            Route::get('/user/two-factor-secret-key', [TwoFactorSecretKeyController::class, 'show'])
                ->middleware(['password.confirm'])
                ->name('two-factor.secret-key');

            Route::get('/user/two-factor-recovery-codes', [RecoveryCodeController::class, 'index'])
                ->middleware(['password.confirm'])
                ->name('two-factor.recovery-codes');

            Route::post('/user/two-factor-recovery-codes', [RecoveryCodeController::class, 'store'])
                ->middleware(['password.confirm'])
                ->name('two-factor.regenerate-recovery-codes');
        });
    }

    // Logout Route
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware(['auth:'.config('fortify.guard')])
        ->name('logout');
});
