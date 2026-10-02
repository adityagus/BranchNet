<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::with('user:id,name')->when($request->action, fn ($q, $action) => $q->where('action', 'like', "%{$action}%"))->latest('created_at')->paginate(30)->withQueryString();
        return view('audit-logs.index', compact('logs'));
    }
}
