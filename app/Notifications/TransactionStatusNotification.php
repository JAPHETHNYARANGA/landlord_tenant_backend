<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionStatusNotification extends Notification
{
    use Queueable;

    protected $status;
    protected $amount;

    /**
     * Create a new notification instance.
     *
     * @param string $status
     * @param float $amount
     */
    public function __construct($status, $amount)
    {
        $this->status = $status;
        $this->amount = $amount;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];  // Adjust as per your notification channels
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Transaction Status Update')
            ->line('Your recent transaction of ' . $this->amount . ' has been ' . $this->status . '.');
    }

    /**
     * Build the database representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'status' => $this->status,
            'amount' => $this->amount,
        ];
    }
}
