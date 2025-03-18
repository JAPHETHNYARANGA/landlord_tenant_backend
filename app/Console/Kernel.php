<?php

namespace App\Console;

use App\Models\RentPayments;
use App\Models\Tenant;
use App\Notifications\RentReminderNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            $tenants = Tenant::all();
            foreach ($tenants as $tenant) {
                $currentMonth = now()->format('Y-m');
                $rentPaid = RentPayments::where('tenant_id', $tenant->id)
                    ->where('month', $currentMonth)
                    ->exists();
        
                if (!$rentPaid && now()->day > 5) {
                    // Send reminder to tenant
                    $tenant->notify(new RentReminderNotification());
        
                    // Notify landlord
                    $landlord = $tenant->property->landlord;
                    $landlord->notify(new RentReminderNotification($tenant));
        
                    // Notify admin (if needed)
                    // $admin = User::where('role', 'admin')->first();
                    // $admin->notify(new RentReminderNotification($tenant));
                }
            }
        })->monthlyOn(5, '08:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}