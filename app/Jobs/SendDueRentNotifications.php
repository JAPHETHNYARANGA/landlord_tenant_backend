<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\Landlord;
use App\Models\Admin;
use App\Models\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class SendDueRentNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // Get the current date
        $today = Carbon::today();
        $currentMonth = $today->format('Y-m'); // Get the current month in 'YYYY-MM' format

        // Fetch all tenants
        $tenants = Tenant::with(['rentPayments' => function ($query) use ($today, $currentMonth) {
            // Fetch rent payments that are unpaid and due before today
            $query->where('payment_date', '<', $today)
                ->where('status', 'unpaid');
        }])->get();

        foreach ($tenants as $tenant) {
            // Check if the tenant has already paid rent for the current month
            $hasPaidCurrentMonth = $tenant->rentPayments()
                ->where('month', $currentMonth)
                ->where('status', 'paid')
                ->exists();

                if ($hasPaidCurrentMonth) {
                    // Delete tenant notifications
                    Notifications::where('user_id', $tenant->id)
                        ->where('user_type', 'tenant')
                        ->where(function ($query) use ($currentMonth) {
                            $query->where('message', 'like', '%overdue rent%')
                                  ->orWhere('message', 'like', '%not made any rent payments%');
                        })
                        ->delete();
                
                    // Delete landlord notifications
                    if ($tenant->property && $tenant->property->landlord) {
                        Notifications::where('user_id', $tenant->property->landlord->id)
                            ->where('user_type', 'landlord')
                            ->where('message', 'like', "%{$tenant->name}%")
                            ->delete();
                    }
                
                    // Delete admin notifications
                    $admins = Admin::all();
                    foreach ($admins as $admin) {
                        Notifications::where('user_id', $admin->id)
                            ->where('user_type', 'admin')
                            ->where('message', 'like', "%{$tenant->name}%")
                            ->delete();
                    }
                }

            // Skip this tenant if rent has already been paid for the current month
            if ($hasPaidCurrentMonth) {
                continue;
            }

            // Case 1: Tenant has no rent payments at all
            if ($tenant->rentPayments->isEmpty()) {
                // Send notification to the tenant (no rent payments exist)
                Notifications::create([
                    'user_type' => 'tenant',
                    'user_id' => $tenant->id,
                    'message' => "You have not made any rent payments for the month of {$currentMonth}. Please make your payment as soon as possible.",
                    'status' => 'unread',
                ]);

                // Send notification to the landlord
                if ($tenant->property && $tenant->property->landlord) {
                    Notifications::create([
                        'user_type' => 'landlord',
                        'user_id' => $tenant->property->landlord->id,
                        'message' => "Tenant {$tenant->name} has not made any rent payments for property {$tenant->property->name} for the month of {$currentMonth}.",
                        'status' => 'unread',
                    ]);
                }

                // Send notification to all admins
                $admins = Admin::all();
                foreach ($admins as $admin) {
                    Notifications::create([
                        'user_type' => 'admin',
                        'user_id' => $admin->id,
                        'message' => "Tenant {$tenant->name} has not made any rent payments for property {$tenant->property->name} for the month of {$currentMonth}.",
                        'status' => 'unread',
                    ]);
                }
            }
            // Case 2: Tenant has unpaid rent payments
            elseif ($tenant->rentPayments->isNotEmpty()) {
                // Iterate through the rent payments to check for unpaid or overdue payments
                foreach ($tenant->rentPayments as $payment) {
                    if ($payment->status == 'unpaid' && $payment->payment_date < $today) {
                        // Send notification to the tenant (overdue rent)
                        Notifications::create([
                            'user_type' => 'tenant',
                            'user_id' => $tenant->id,
                            'message' => "Your rent for the month of {$currentMonth} is overdue. Please make the payment as soon as possible.",
                            'status' => 'unread',
                        ]);

                        // Send notification to the landlord
                        if ($tenant->property && $tenant->property->landlord) {
                            Notifications::create([
                                'user_type' => 'landlord',
                                'user_id' => $tenant->property->landlord->id,
                                'message' => "Tenant {$tenant->name} has overdue rent for the month of {$currentMonth} for property {$tenant->property->name}.",
                                'status' => 'unread',
                            ]);
                        }

                        // Send notification to all admins
                        $admins = Admin::all();
                        foreach ($admins as $admin) {
                            Notifications::create([
                                'user_type' => 'admin',
                                'user_id' => $admin->id,
                                'message' => "Tenant {$tenant->name} has overdue rent for the month of {$currentMonth} for property {$tenant->property->name}.",
                                'status' => 'unread',
                            ]);
                        }
                    }
                }
            }
        }
    }
}
