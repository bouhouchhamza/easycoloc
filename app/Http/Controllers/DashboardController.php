<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $service): View
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        return view('dashboard', $service->forUser($user));
    }
}
