<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class CampaignEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $subject,
        public string $content,
        public ?string $campaignSendId = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->subject)
            ->line(new HtmlString($this->content));

        if ($this->campaignSendId) {
            $trackingPixelUrl = route('campaign.track.open', ['send' => $this->campaignSendId]);
            $message->line(new HtmlString('<img src="'.$trackingPixelUrl.'" width="1" height="1" style="display:none;" />'));
        }

        return $message;
    }
}
