<?php

use App\Http\Controllers\Admin\ConnectionRequestController as AdminConnectionRequestController;
use App\Http\Controllers\Admin\FieldMissionController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\MaintenanceIncidentController;
use App\Http\Controllers\Admin\MeterController as AdminMeterController;
use App\Http\Controllers\Admin\MeterReadingController as AdminMeterReadingController;
use App\Http\Controllers\Admin\NetworkAssetController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Admin\SystemNotificationController;
use App\Http\Controllers\Admin\TariffController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\WaterDashboardController;
use App\Http\Controllers\Admin\WaterSettingController;
use App\Http\Controllers\Admin\WaterZoneController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Client\ConnectionRequestController as ClientConnectionRequestController;
use App\Http\Controllers\Client\InvoiceController as ClientInvoiceController;
use App\Http\Controllers\Client\MeterReadingController as ClientMeterReadingController;
use App\Http\Controllers\Client\SupportTicketController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FieldAgent\MeterReadingController as FieldAgentMeterReadingController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::view('/guide', 'guide')->name('guide');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', [UserProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [UserProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [UserProfileController::class, 'updatePassword'])->name('profile.password.update');
});

Route::middleware(['auth', 'role:administrator'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/guide', 'admin.guide')->name('guide');
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::put('/users/{user}/role', [UserManagementController::class, 'updateRole'])->name('users.role.update');
    Route::get('/meters', [AdminMeterController::class, 'index'])->name('meters.index');
    Route::post('/meters', [AdminMeterController::class, 'store'])->name('meters.store');
    Route::put('/meters/{waterMeter}', [AdminMeterController::class, 'update'])->name('meters.update');
    Route::post('/meters/{waterMeter}/assignment', [AdminMeterController::class, 'assign'])->name('meters.assignment');
    Route::post('/meters/{waterMeter}/events', [AdminMeterController::class, 'storeEvent'])->name('meters.events.store');
    Route::get('/water', WaterDashboardController::class)->name('water.dashboard');
    Route::get('/water/invoices/{invoice}/download', [AdminInvoiceController::class, 'download'])->name('water.invoices.download');
    Route::post('/water/invoices/{invoice}/send', [AdminInvoiceController::class, 'send'])->name('water.invoices.send');
    Route::get('/subscriptions', [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::patch('/subscriptions/{subscription}/status', [AdminSubscriptionController::class, 'updateStatus'])->name('subscriptions.status.update');
    Route::patch('/subscriptions/{subscription}/holder', [AdminSubscriptionController::class, 'transferHolder'])->name('subscriptions.holder.transfer');
    Route::patch('/subscriptions/{subscription}/occupant', [AdminSubscriptionController::class, 'updateOccupant'])->name('subscriptions.occupant.update');
    Route::get('/zones', [WaterZoneController::class, 'index'])->name('zones.index');
    Route::post('/zones', [WaterZoneController::class, 'store'])->name('zones.store');
    Route::put('/zones/{waterZone}', [WaterZoneController::class, 'update'])->name('zones.update');
    Route::post('/zones/{waterZone}/subscriptions', [WaterZoneController::class, 'assignSubscription'])->name('zones.subscriptions.assign');
    Route::put('/zones/{waterZone}/agents', [WaterZoneController::class, 'assignAgents'])->name('zones.agents.sync');
    Route::get('/network', [NetworkAssetController::class, 'index'])->name('network.index');
    Route::post('/network/assets', [NetworkAssetController::class, 'store'])->name('network.assets.store');
    Route::put('/network/assets/{waterNetworkAsset}', [NetworkAssetController::class, 'update'])->name('network.assets.update');
    Route::post('/network/incidents', [MaintenanceIncidentController::class, 'store'])->name('network.incidents.store');
    Route::patch('/network/incidents/{maintenanceIncident}', [MaintenanceIncidentController::class, 'update'])->name('network.incidents.update');
    Route::post('/network/missions', [FieldMissionController::class, 'store'])->name('network.missions.store');
    Route::patch('/network/missions/{fieldMission}', [FieldMissionController::class, 'update'])->name('network.missions.update');
    Route::post('/network/notifications', [SystemNotificationController::class, 'store'])->name('network.notifications.store');
    Route::patch('/network/notifications/{systemNotification}/read', [SystemNotificationController::class, 'markAsRead'])->name('network.notifications.read');
    Route::get('/tariffs', [TariffController::class, 'index'])->name('tariffs.index');
    Route::post('/tariffs', [TariffController::class, 'store'])->name('tariffs.store');
    Route::patch('/water/readings/{meterReading}/verify', [AdminMeterReadingController::class, 'verify'])->name('water.readings.verify');
    Route::patch('/water/requests/{connectionRequest}', [AdminConnectionRequestController::class, 'update'])->name('water.requests.update');
    Route::post('/water/requests/{connectionRequest}/activate', [AdminConnectionRequestController::class, 'activate'])->name('water.requests.activate');
    Route::post('/water/readings/{meterReading}/invoice', [AdminMeterReadingController::class, 'createInvoice'])->name('water.readings.invoice');
    Route::patch('/water/payments/{payment}', [AdminPaymentController::class, 'update'])->name('water.payments.update');
    Route::patch('/water/tickets/{supportTicket}', [AdminSupportTicketController::class, 'update'])->name('water.tickets.update');
    Route::put('/water/settings', [WaterSettingController::class, 'update'])->name('water.settings.update');
});

Route::middleware(['auth', 'role:field_agent'])->prefix('agent')->name('agent.')->group(function () {
    Route::view('/dashboard', 'dashboards.field-agent')->name('dashboard');
    Route::get('/readings', [FieldAgentMeterReadingController::class, 'index'])->name('readings.index');
    Route::post('/readings', [FieldAgentMeterReadingController::class, 'store'])->name('readings.store');
});

Route::middleware(['auth', 'role:customer'])->prefix('client')->name('client.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/requests', [ClientConnectionRequestController::class, 'index'])->name('requests.index');
    Route::post('/requests', [ClientConnectionRequestController::class, 'store'])->name('requests.store');
    Route::post('/requests/{connectionRequest}/accept', [ClientConnectionRequestController::class, 'accept'])->name('requests.accept');
    Route::get('/invoices', [ClientInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}/print', [ClientInvoiceController::class, 'print'])->name('invoices.print');
    Route::get('/invoices/{invoice}/download', [ClientInvoiceController::class, 'download'])->name('invoices.download');
    Route::post('/invoices/{invoice}/send', [ClientInvoiceController::class, 'send'])->name('invoices.send');
    Route::post('/invoices/{invoice}/payments', [ClientInvoiceController::class, 'storePayment'])->name('invoices.payments.store');
    Route::get('/readings', [ClientMeterReadingController::class, 'index'])->name('readings.index');
    Route::post('/readings', [ClientMeterReadingController::class, 'store'])->name('readings.store');
    Route::post('/readings/{meterReading}/invoice', [ClientInvoiceController::class, 'createFromReading'])->name('readings.invoice');
    Route::get('/support', [SupportTicketController::class, 'index'])->name('support.index');
    Route::post('/support', [SupportTicketController::class, 'store'])->name('support.store');
});
