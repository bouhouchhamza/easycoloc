<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Models\Colocation;

Route::prefix('admin')->middleware(['auth','role'])->group(function(){
    Route::get('/', [AdminDashboardController::class,'index'])->name('admin.dashboard');
});

Route::get('/', function () {
    return view('welcome');
});
Route::get('/admin', function(){
    return 'Admin dashboard';
})->middleware(['auth'],'role:admin');
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/colocations/{colocation}/leave',[ColocationsController::class, 'leave'])->name('colocations.leave');
    Route::post('/colocations/{colocation}/cancel',[ColocationController::class, 'cancel'])->name('colocations.cancel');
});

require __DIR__.'/auth.php';
