<?php

use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\ProfileController;
use App\Mail\TenantWelcomeMail;
use App\Modules\Tenants\Services\BeemsSmsService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Test route for SMS and email - remove in production
Route::get('/test-sms', function () {
    $smsService = app(BeemsSmsService::class);

    // Test SMS to user's phone
    $testPhone = '255682676819';
    $testMessage = 'Test message from House Managers - SMS is working!';

    $smsResult = $smsService->send($testPhone, $testMessage);

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
});

// The real /dashboard route lives in App\Modules\Dashboard now.

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('message-templates', MessageTemplateController::class);
});

require __DIR__.'/auth.php';
