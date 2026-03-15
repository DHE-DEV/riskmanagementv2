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

        Route::post('/profile/update-personal', [\App\Http\Controllers\Customer\ProfileController::class, 'updatePersonal'])
            ->name('profile.update-personal');

        Route::post('/profile/avatar', [\App\Http\Controllers\Customer\ProfileController::class, 'uploadAvatar'])
            ->name('profile.upload-avatar');

        Route::delete('/profile/avatar', [\App\Http\Controllers\Customer\ProfileController::class, 'deleteAvatar'])
            ->name('profile.delete-avatar');

        Route::post('/profile/update-password', [\App\Http\Controllers\Customer\ProfileController::class, 'updatePassword'])
            ->name('profile.update-password');

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

        // Passolution OAuth routes (require auth)
        Route::get('/passolution/authorize', [\App\Http\Controllers\Customer\PassolutionOAuthController::class, 'redirect'])
            ->name('passolution.authorize');

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

        // Customer Events (eigene Ereignisse)
        Route::get('/events', function () {
            $hasEvents = \App\Models\CustomEvent::where('customer_id', auth('customer')->id())->exists();
            return view('customer.events.index', compact('hasEvents'));
        })->name('events');

        // Phone Numbers (Rufnummern)
        Route::prefix('phone-numbers')->name('phone-numbers.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\PhoneNumberController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Customer\PhoneNumberController::class, 'store'])->name('store');
            Route::put('/{phoneNumber}', [\App\Http\Controllers\Customer\PhoneNumberController::class, 'update'])->name('update');
            Route::delete('/{phoneNumber}', [\App\Http\Controllers\Customer\PhoneNumberController::class, 'destroy'])->name('destroy');
            Route::post('/reorder', [\App\Http\Controllers\Customer\PhoneNumberController::class, 'reorder'])->name('reorder');
        });

        // Email Addresses (E-Mail Adressen)
        Route::prefix('email-addresses')->name('email-addresses.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\EmailAddressController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Customer\EmailAddressController::class, 'store'])->name('store');
            Route::put('/{emailAddress}', [\App\Http\Controllers\Customer\EmailAddressController::class, 'update'])->name('update');
            Route::delete('/{emailAddress}', [\App\Http\Controllers\Customer\EmailAddressController::class, 'destroy'])->name('destroy');
            Route::post('/reorder', [\App\Http\Controllers\Customer\EmailAddressController::class, 'reorder'])->name('reorder');
        });

        // Websites (Web)
        Route::prefix('websites')->name('websites.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\WebsiteController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Customer\WebsiteController::class, 'store'])->name('store');
            Route::put('/{website}', [\App\Http\Controllers\Customer\WebsiteController::class, 'update'])->name('update');
            Route::delete('/{website}', [\App\Http\Controllers\Customer\WebsiteController::class, 'destroy'])->name('destroy');
            Route::post('/reorder', [\App\Http\Controllers\Customer\WebsiteController::class, 'reorder'])->name('reorder');
        });

        // Branch Contacts (Kontakte pro Adresse)
        Route::prefix('branch-contacts')->name('branch-contacts.')->group(function () {
            Route::post('/', [\App\Http\Controllers\Customer\BranchContactController::class, 'store'])->name('store');
            Route::put('/{branchContact}', [\App\Http\Controllers\Customer\BranchContactController::class, 'update'])->name('update');
            Route::delete('/{branchContact}', [\App\Http\Controllers\Customer\BranchContactController::class, 'destroy'])->name('destroy');
        });

        // Org Nodes (Organisationsstruktur)
        Route::prefix('org-nodes')->name('org-nodes.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\OrgNodeController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Customer\OrgNodeController::class, 'store'])->name('store');
            Route::put('/{orgNode}', [\App\Http\Controllers\Customer\OrgNodeController::class, 'update'])->name('update');
            Route::delete('/{orgNode}', [\App\Http\Controllers\Customer\OrgNodeController::class, 'destroy'])->name('destroy');
            Route::post('/reorder', [\App\Http\Controllers\Customer\OrgNodeController::class, 'reorder'])->name('reorder');
            Route::post('/{orgNode}/move', [\App\Http\Controllers\Customer\OrgNodeController::class, 'move'])->name('move');
        });

        // Departments (Abteilungen)
        Route::prefix('departments')->name('departments.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\DepartmentController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Customer\DepartmentController::class, 'store'])->name('store');
            Route::put('/{department}', [\App\Http\Controllers\Customer\DepartmentController::class, 'update'])->name('update');
            Route::delete('/{department}', [\App\Http\Controllers\Customer\DepartmentController::class, 'destroy'])->name('destroy');
            Route::post('/reorder', [\App\Http\Controllers\Customer\DepartmentController::class, 'reorder'])->name('reorder');
        });

        // Employees (Benutzer)
        Route::prefix('employees')->name('employees.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\EmployeeController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Customer\EmployeeController::class, 'store'])->name('store');
            Route::put('/{employee}', [\App\Http\Controllers\Customer\EmployeeController::class, 'update'])->name('update');
            Route::delete('/{employee}', [\App\Http\Controllers\Customer\EmployeeController::class, 'destroy'])->name('destroy');
        });

        // Customer Settings
        Route::get('/settings', function () {
            return view('customer.settings.index');
        })->name('settings');

        // API Token routes
        Route::prefix('api-tokens')->name('api-tokens.')->group(function () {
            Route::post('/generate', [\App\Http\Controllers\Customer\ApiTokenController::class, 'generate'])->name('generate');
            Route::post('/revoke', [\App\Http\Controllers\Customer\ApiTokenController::class, 'revoke'])->name('revoke');
            Route::get('/status', [\App\Http\Controllers\Customer\ApiTokenController::class, 'status'])->name('status');
        });

        // Notification Settings routes (Benachrichtigungs-Abonnement)
        Route::prefix('notification-settings')->name('notification-settings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'index'])->name('index');
            Route::post('/toggle', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'toggleNotifications'])->name('toggle');
            Route::get('/stats', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'stats'])->name('stats');
            Route::get('/rules-json', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'rulesJson'])->name('rules.json');
            Route::get('/history', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'history'])->name('history');

            // Rules
            Route::get('/rules/create', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'createRule'])->name('rules.create');
            Route::get('/rules/{id}/edit', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'editRule'])->name('rules.edit');
            Route::delete('/rules/{id}', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'deleteRule'])->name('rules.destroy');
            Route::post('/rules/{id}/test', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'sendRuleTestMail'])->name('rules.test');

            // Templates
            Route::get('/templates', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'templateIndex'])->name('templates.index');
            Route::get('/templates/create', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'createTemplate'])->name('templates.create');
            Route::get('/templates/{id}/edit', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'editTemplate'])->name('templates.edit');
            Route::delete('/templates/{id}', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'deleteTemplate'])->name('templates.destroy');
            Route::post('/templates/{id}/test', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'sendTestMail'])->name('templates.test');
            Route::get('/logs', [\App\Http\Controllers\Customer\NotificationSettingsController::class, 'logs'])->name('logs');
        });

        // Notification routes
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Customer\NotificationController::class, 'index'])->name('index');
            Route::post('/{id}/read', [\App\Http\Controllers\Customer\NotificationController::class, 'markAsRead'])->name('mark-read');
            Route::post('/mark-all-read', [\App\Http\Controllers\Customer\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
            Route::delete('/{id}', [\App\Http\Controllers\Customer\NotificationController::class, 'delete'])->name('delete');
        });
    });

    // Passolution OAuth callback (no auth required - user returns from external OAuth)
    Route::get('/passolution/callback', [\App\Http\Controllers\Customer\PassolutionOAuthController::class, 'callback'])
        ->name('passolution.callback');

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
        // Verification link from email - no auth required
        Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\Auth\Customer\VerifyEmailController::class, '__invoke'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        // Redirect /email/verify to /customer/login
        Route::get('/email/verify', function () {
            return redirect()->route('customer.login');
        })->name('verification.notice');

        Route::middleware(['auth:'.config('fortify.guard')])->group(function () {
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
