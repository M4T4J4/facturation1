<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConnectionRequest;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\WaterSetting;
use Illuminate\View\View;

class WaterDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.water.index', [
            'requestCount' => ConnectionRequest::whereIn('status', ['received', 'under_review', 'quoted', 'approved'])->count(),
            'subscriptionCount' => Subscription::count(),
            'unpaidInvoiceCount' => Invoice::whereIn('status', ['unpaid', 'partial'])->count(),
            'pendingPaymentCount' => Payment::where('status', 'pending')->count(),
            'connectionRequests' => ConnectionRequest::with('user')->latest()->limit(8)->get(),
            'readings' => MeterReading::with('subscription.user', 'waterMeter')->where('status', 'submitted')->latest('read_at')->limit(8)->get(),
            'readingsToInvoice' => MeterReading::with('subscription.user', 'waterMeter')
                ->where('status', 'verified')
                ->whereDoesntHave('invoice')
                ->whereHas('subscription', fn ($query) => $query->where('status', 'active'))
                ->latest('read_at')
                ->limit(8)
                ->get(),
            'recentInvoices' => Invoice::with('subscription.user')->latest('created_at')->limit(8)->get(),
            'payments' => Payment::with('invoice.subscription.user')->where('status', 'pending')->latest()->limit(8)->get(),
            'tickets' => SupportTicket::with('user')->whereIn('status', ['open', 'in_progress'])->latest()->limit(8)->get(),
            'settings' => WaterSetting::current(),
        ]);
    }
}
