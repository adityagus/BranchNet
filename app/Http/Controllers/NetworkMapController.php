<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NetworkMapController extends Controller
{
    public function __invoke(Request $request): View
    {
        $branch = $request->filled('branch_id') ? Branch::with(['devices' => fn ($q) => $q->orderBy('device_type')->limit(30)])->find($request->integer('branch_id')) : Branch::with(['devices' => fn ($q) => $q->orderBy('device_type')->limit(30)])->orderBy('name')->first();
        return view('network-map', ['branch' => $branch, 'branches' => Branch::orderBy('name')->get(['id', 'name'])]);
    }
}
