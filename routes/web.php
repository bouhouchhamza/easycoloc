<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ColocationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettlementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/invitations/{token}', [InvitationController::class, 'landing'])
    ->name('invitations.landing');

Route::middleware(['auth', 'not_banned'])->group(function () {
    Route::get('/invitations/{token}/accept', [InvitationController::class, 'accept'])
        ->name('invitations.accept');
});

Route::middleware(['auth', 'verified', 'not_banned'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/colocations', [ColocationController::class, 'index'])->name('colocations.index');
    Route::get('/colocations/create', [ColocationController::class, 'create'])->name('colocations.create');
    Route::get('/colocations/join', [ColocationController::class, 'join'])->name('colocations.join');
    Route::post('/colocations', [ColocationController::class, 'store'])->name('colocations.store');
    Route::post('/colocations/token', [ColocationController::class, 'submitToken'])->name('colocations.token.submit');
    Route::post('/colocations/{colocation}/invite-email', [ColocationController::class, 'sendInvitationEmail'])
        ->name('colocations.invite.email');
    Route::get('/colocations/invite/{token}', [ColocationController::class, 'invite'])->name('colocations.invite');
    Route::post('/colocations/invite/respond', [ColocationController::class, 'respondInvitation'])->name('colocations.invite.respond');
    Route::get('/colocations/{colocation}', [ColocationController::class, 'show'])->name('colocations.show');
    Route::post('/colocations/{colocation}/leave', [ColocationController::class, 'leave'])->name('colocations.leave');
    Route::post('/colocations/{colocation}/remove/{user}', [ColocationController::class, 'removeMember'])->name('colocations.remove-member');
    Route::post('/colocations/{colocation}/cancel', [ColocationController::class, 'cancel'])->name('colocations.cancel');

    Route::post('/colocations/{colocation}/categories', [CategoryController::class, 'store'])->name('colocations.categories.store');
    Route::delete('/colocations/{colocation}/categories/{category}', [CategoryController::class, 'destroy'])->name('colocations.categories.destroy');

    Route::post('/colocations/{colocation}/expenses', [ExpenseController::class, 'store'])->name('colocations.expenses.store');
    Route::delete('/colocations/{colocation}/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('colocations.expenses.destroy');

    Route::get('/colocations/{colocation}/settlement', [SettlementController::class, 'show'])->name('colocations.settlement.show');
    Route::post('/colocations/{colocation}/payments', [PaymentController::class, 'store'])->name('colocations.payments.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:global_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::patch('/users/{user}/ban-status', [AdminDashboardController::class, 'updateBanStatus'])
            ->name('users.ban-status');
    });

require __DIR__.'/auth.php';
  
