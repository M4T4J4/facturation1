<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WaterSetting;
use App\UserRole;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return match ($user->role) {
            UserRole::Administrator => view('dashboards.administrator', [
                'customerCount' => User::where('role', UserRole::Customer->value)->count(),
                'fieldAgentCount' => User::where('role', UserRole::FieldAgent->value)->count(),
            ]),
            UserRole::FieldAgent => view('dashboards.field-agent', [
                'activeSubscriptionCount' => Subscription::where('status', 'active')->count(),
            ]),
            UserRole::Customer => view('dashboards.customer', [
                'subscriptions' => $user->subscriptions()
                    ->with([
                        'waterMeter.readings' => fn ($query) => $query->latest('read_at'),
                        'invoices' => fn ($query) => $query->latest('period'),
                    ])
                    ->withCount(['invoices as unpaid_invoice_count' => fn ($query) => $query->whereIn('status', ['unpaid', 'partial'])])
                    ->latest()
                    ->get(),
                'latestRequest' => $user->connectionRequests()->latest()->first(),
                'unpaidInvoices' => Invoice::query()->whereHas('subscription', fn ($query) => $query->where('user_id', $user->id))->whereIn('status', ['unpaid', 'partial'])->count(),
                'settings' => WaterSetting::current(),
            ]),
        };
    }
}
