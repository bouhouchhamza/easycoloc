<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBanStatusRequest;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(AdminService $service): View
    {
        return view('admin.dashboard', $service->dashboardData());
    }

    public function updateBanStatus(
        UpdateBanStatusRequest $request,
        User $user,
        AdminService $service
    ): RedirectResponse {
        try {
            $service->updateBanStatus($user, $request->boolean('is_banned'));
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'User ban status updated.');
    }
}
