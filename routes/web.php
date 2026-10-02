<?php

use App\Http\Controllers\BranchDeviceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\NetworkAgentController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\MonitoringHistoryController;
use App\Http\Controllers\NetworkMapController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));
Route::get('/dashboard', DashboardController::class)->name('dashboard');
Route::get('/network-map', NetworkMapController::class)->name('network-map');
Route::get('/monitoring-history', [MonitoringHistoryController::class, 'index'])->name('monitoring-history');
Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs');
Route::get('/settings', SettingsController::class)->name('settings');
Route::resource('branches', BranchController::class)->except(['show']);
Route::post('/branches/sync-sopiga', [BranchController::class, 'syncFromSopiga'])->name('branches.sync-sopiga');
Route::get('/branches/{branch}/devices', [BranchDeviceController::class, 'index'])->name('branches.devices');
Route::get('/branches/{branch}/devices/create', [DeviceController::class, 'create'])->name('branches.devices.create');
Route::post('/branches/{branch}/devices', [DeviceController::class, 'store'])->name('branches.devices.store');
Route::resource('agents', NetworkAgentController::class)->parameters(['agents' => 'agent'])->except(['show']);
Route::resource('alerts', AlertController::class)->except(['show']);
Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
Route::get('/devices/{device}', [DeviceController::class, 'show'])->name('devices.show');
Route::get('/devices/{device}/edit', [DeviceController::class, 'edit'])->name('devices.edit');
Route::put('/devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');
Route::get('/devices/{device}/history', [DeviceController::class, 'history'])->name('devices.history');
Route::post('/devices/{device}/check', [DeviceController::class, 'check'])->name('devices.check');
