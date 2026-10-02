<?php

namespace App\Http\Controllers;

use App\Services\NetworkHealthService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(NetworkHealthService $health): View { return view('dashboard', ['branches' => $health->branches(), 'summary' => $health->summary(request('branch_id'))]); }
}
