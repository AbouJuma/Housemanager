<?php

use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\ProfileController;
use App\Mail\TenantWelcomeMail;
use App\Modules\Companies\Models\Company;
use App\Modules\Sms\Services\SmsManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Test route for SMS and email - remove in production. Goes through the
// same App\Modules\Sms\Services\SmsManager every real notification uses
// (falling back to the platform Beem account per BEEMS_* env vars if
// the first company has no SMS integration configured), not the old
// standalone BeemsSmsService (removed - see the messaging
// consolidation work).
// Only a signed-in super admin can trigger it - it sends a real SMS/email.
Route::get('/test-sms', function () {
    abort_unless(auth()->user()?->isSaasOwner(), 404);

    $company = Company::query()->first();

    // Test SMS to user's phone
    $testPhone = '0682676819';
    $testMessage = 'Test message from House Managers - SMS is working!';

    $smsResult = $company
        ? app(SmsManager::class)->send($company, $testPhone, $testMessage)
        : ['success' => false, 'message' => 'No company found to send from.'];

    // Test email to user's email
    $testEmail = 'kisiakiabou@gmail.com';
    $testData = [
        'tenant_name' => 'Test User',
        'company_name' => 'Test Company',
        'phone' => $testPhone,
        'control_number' => 'CTRL-000001',
        'bank_name' => 'Test Bank',
        'account_number' => '1234567890',
        'rent_amount' => 500000,
    ];

    try {
        Mail::to($testEmail)->send(new TenantWelcomeMail($testData));
        $emailResult = true;
    } catch (\Exception $e) {
        $emailResult = false;
    }

    return response()->json([
        'sms_sent' => $smsResult,
        'email_sent' => $emailResult,
        'message' => 'Test completed. Check logs for details.',
    ]);
})->middleware('auth');

// The real /dashboard route lives in App\Modules\Dashboard now.

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('message-templates', MessageTemplateController::class);
});

require __DIR__.'/auth.php';

// The Android app: the newest uploaded APK, linked under every sign-in
// page (public), and the super admin's page for publishing a new one.
Route::get('/download/android', [\App\Http\Controllers\AppReleaseController::class, 'download'])->name('app.download');
Route::middleware('auth')->group(function (): void {
    Route::get('/mobile-app', [\App\Http\Controllers\AppReleaseController::class, 'index'])->name('app-releases.index');
    Route::post('/mobile-app', [\App\Http\Controllers\AppReleaseController::class, 'store'])->name('app-releases.store');
});

// Live username availability for <x-username-field> on the staff,
// tenant, landlord and buyer forms.
Route::get('/username-check', \App\Http\Controllers\UsernameAvailabilityController::class)
    ->middleware(['auth', 'throttle:60,1'])
    ->name('username.check');

// Language switcher - English / Kiswahili (see App\Http\Middleware\SetLocale).
Route::post('/locale/{locale}', \App\Http\Controllers\LocaleController::class)->name('locale.switch');
