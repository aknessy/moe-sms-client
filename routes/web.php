<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FinanceAndAccountController;
use App\Http\Controllers\HumanResourceController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PensionGratuityController;
use App\Http\Controllers\ProfileSettingsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\VoterRegistryController;

Route::get('/', [LoginController::class, 'index'])->name('login');
Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function(){
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Uploaded documents are staff records, so they sit behind the session too.
Route::middleware('auth.session')->get('/storage/{path}', function (string $path) {
    abort_unless(Storage::disk('local')->exists($path), 404);

    return Storage::disk('local')->response($path);
})->where('path', '.*')->name('storage.local');

// Everything past this point requires a valid session.
Route::middleware('auth.session')->prefix('dashboard')->group(function(){
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile-settings', [ProfileSettingsController::class, 'index'])->name('profile-settings');
    Route::get('/change-password', [ProfileSettingsController::class, 'changePassword'])->name('change-password');

    Route::prefix('human-resources')->group(function(){
        Route::get('/leave-management', [HumanResourceController::class, 'leave'])->name('hr-leave');
        Route::get('/promotion-exercises', [HumanResourceController::class, 'promotions'])->name('hr-promotions');
        Route::get('/performance-appraisal', [HumanResourceController::class, 'appraisal'])->name('hr-appraisal');
        Route::get('/recruitment', [HumanResourceController::class, 'recruitment'])->name('hr-recruitment');
        Route::get('/training', [HumanResourceController::class, 'training'])->name('hr-training');
        Route::get('/establishment-strength', [HumanResourceController::class, 'establishment'])->name('hr-establishment');
    });

    Route::prefix('staff')->group(function(){
        Route::get('/', [StaffController::class, 'index']);
        Route::get('/basic-info', [StaffController::class, 'index'])->name('basic-info');
        Route::get('/edu-progress', [StaffController::class, 'edu_progress'])->name('edu-progress');
        Route::get('/promotion', [StaffController::class, 'promotions'])->name('promotion');
        Route::get('/leave-record', [StaffController::class, 'leave'])->name('leave-record');
        Route::get('/referee', [StaffController::class, 'referee'])->name('referee');
        Route::get('/next-of-kin', [StaffController::class, 'next_of_kin'])->name('next-of-kin');
        Route::get('/career', [StaffController::class, 'career'])->name('career');

        Route::post('/create',[StaffController::class, 'create'])->name('create');
    });

    Route::prefix('registry')->group(function(){
        Route::get('/nominal-roll', [StaffController::class, 'nominal_roll'])->name('nominal-roll');
        Route::get('/bio-data', [StaffController::class, 'bio_data'])->name('bio-data');

        Route::get('/posting-deployment', [StaffController::class, 'posting_deployment'])->name('posting-deployment');
        Route::post('/posting-deployment', [StaffController::class, 'store_posting'])->name('posting-deployment.store');

        Route::get('/confirmation', [StaffController::class, 'confirmation'])->name('confirmation');
        Route::post('/confirmation', [StaffController::class, 'store_confirmation'])->name('confirmation.store');

        Route::get('/record-of-service', [StaffController::class, 'record_of_service'])->name('record-of-service');

        Route::get('/query-discipline', [StaffController::class, 'query_discipline'])->name('query-discipline');
        Route::post('/query-discipline', [StaffController::class, 'store_query'])->name('query-discipline.store');
        Route::get('/query-discipline/{ulid}/response', [StaffController::class, 'query_response'])->name('query-response');
        Route::post('/query-discipline/{ulid}/decision', [StaffController::class, 'decide_query'])->name('query-response.decide');

        Route::get('/record-amendments', [StaffController::class, 'record_amendments'])->name('record-amendments');
        Route::post('/record-amendments', [StaffController::class, 'store_amendment'])->name('record-amendments.store');
        Route::post('/record-amendments/{ulid}/review', [StaffController::class, 'review_amendment'])->name('record-amendments.review');
    });

    Route::prefix('pension-gratuity')->group(function(){
        Route::get('/retirement-exit', [PensionGratuityController::class, 'retirement_exit'])->name('retirement-exit');
        Route::post('/retirement-exit', [PensionGratuityController::class, 'store_exit'])->name('retirement-exit.store');

        Route::get('/cps', [PensionGratuityController::class, 'cps'])->name('cps');
        Route::get('/deceased', [PensionGratuityController::class, 'deceased'])->name('deceased');
        Route::post('/deceased', [PensionGratuityController::class, 'store_deceased'])->name('deceased.store');
        Route::get('/deceased/export', [PensionGratuityController::class, 'deceased_export'])->name('deceased.export');

        Route::post('/benefit-payments', [PensionGratuityController::class, 'store_payment'])->name('benefit-payments.store');
        Route::get('/benefit-payments/{ulid}/receipt', [PensionGratuityController::class, 'receipt'])->name('benefit-payments.receipt');
    });

    Route::prefix('finance-account')->group(function(){
        Route::get('/salary-structure', [FinanceAndAccountController::class, 'salaryStructure'])->name('salary-structure');
        Route::get('/allowances', [FinanceAndAccountController::class, 'allowances'])->name('allowances');
        Route::get('/deductions', [FinanceAndAccountController::class, 'deductions'])->name('staff-deductions');
        Route::get('/loans-advances', [FinanceAndAccountController::class, 'loansAdvances'])->name('loans-advances');
        Route::get('/payroll', [FinanceAndAccountController::class, 'payroll'])->name('payroll');
        Route::get('/payslip', [FinanceAndAccountController::class, 'payslip'])->name('payslip');
        Route::get('/arrears-variations', [FinanceAndAccountController::class, 'arrearsVariations'])->name('arrears-variations');
        Route::get('/remittances', [FinanceAndAccountController::class, 'remittances'])->name('remittances');
    });

    Route::prefix('ict')->group(function(){
        Route::get('/', [VoterRegistryController::class, 'index'])->name('voter-registry');
        Route::get('/adhoc-staff', [VoterRegistryController::class, 'adhocStaff'])->name('adhoc-staff');
        Route::get('/voter-registration', [VoterRegistryController::class, 'voterRegistration'])->name('voter-registration');
        Route::get('/polling-units', [VoterRegistryController::class, 'pollingUnits'])->name('polling-units');
        Route::get('/device-register', [VoterRegistryController::class, 'deviceRegister'])->name('device-register');
    });

    Route::prefix('reports')->group(function(){
        Route::get('/nominal-roll', [ReportController::class, 'nominalRoll'])->name('nominal-roll-report');
        Route::get('/nominal-roll/export', [ReportController::class, 'nominalRollExport'])->name('nominal-roll-report.export');

        Route::get('/retirement-projection', [ReportController::class, 'retirementProjection'])->name('retirement-projection');
        Route::get('/retirement-projection/export', [ReportController::class, 'retirementProjectionExport'])->name('retirement-projection.export');

        Route::get('/payroll', [ReportController::class, 'payroll'])->name('payroll-report');

        Route::get('/headcount', [ReportController::class, 'headcount'])->name('headcount-report');
        Route::get('/headcount/export', [ReportController::class, 'headcountExport'])->name('headcount-report.export');
    });

    Route::prefix('docs')->group(function(){
        Route::get('/{uuid}', [DocumentController::class, 'index'])->name('docs');
        Route::get('/passport/{uuid}', [DocumentController::class, 'passport'])->name('passport');
    });

    Route::prefix('config')->group(function(){
        Route::get('/', [ConfigurationController::class, 'index'])->name('config');
        Route::get('/users-roles', [ConfigurationController::class, 'usersRoles'])->name('users-roles');
        Route::post('/users-roles', [ConfigurationController::class, 'storeUser'])->name('users-roles.store');
        Route::post('/users-roles/{user}/toggle', [ConfigurationController::class, 'toggleUser'])->name('users-roles.toggle');
        Route::get('/audit-trail', [ConfigurationController::class, 'auditTrail'])->name('audit-trail');
        Route::get('/reference-data', [ConfigurationController::class, 'referenceData'])->name('reference-data');
        Route::get('/notification-templates', [ConfigurationController::class, 'notificationTemplates'])->name('notification-templates');
        Route::get('/backup-data', [ConfigurationController::class, 'backupData'])->name('backup-data');
    });

});
