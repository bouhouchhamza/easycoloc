<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use Spatie\Permission\Middleware\RoleMiddleware;

class AdminDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','role:admin']);
    }
    public function index(){
        return view('admin.dashboard');
    }
}
