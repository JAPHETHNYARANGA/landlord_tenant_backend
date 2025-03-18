<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Tenant;

class RentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $tenant;

    /**
     * Create a new notification instance.
     *
     * @param Tenant|null $tenant
     */
    public function __construct(Tenant $tenant = null)
    {
        $this->tenant = $tenant;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail']; // You can add other channels like 'database', 'sms', etc.
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        if ($this->tenant) {
            // Notification for landlord or admin
            return (new MailMessage)
                ->subject('Rent Reminder: Tenant ' . $this->tenant->name . ' Has Not Paid Rent')
                ->line('This is a reminder that ' . $this->tenant->name . ' has not paid rent for the current month.')
                ->action('View Tenant Details', url('/tenants/' . $this->tenant->id))
                ->line('Thank you for using our application!');
        } else {
            // Notification for tenant
            return (new MailMessage)
                ->subject('Rent Payment Reminder')
                ->line('This is a reminder that your rent payment for the current month is due.')
                ->action('Pay Rent Now', url('/pay-rent'))
                ->line('Thank you for using our application!');
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            // You can add additional data for database notifications here
        ];
    }
}