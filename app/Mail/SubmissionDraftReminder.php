<?php

namespace App\Mail;

use App\Models\SubmissionReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class SubmissionDraftReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SubmissionReminder $reminder)
    {
        $this->reminder->loadMissing(['submission.competition', 'recipient.profile']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address(config('flowerflow.mail.reply_to'), config('flowerflow.mail.reply_to_name'))],
            subject: 'Recordatorio: envía tu propuesta · Flower Flow',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.submission-draft-reminder',
            text: 'mail.submission-draft-reminder-text',
            with: ['confirmationUrl' => $this->confirmationUrl()],
        );
    }

    public function confirmationUrl(): string
    {
        return URL::temporarySignedRoute(
            'submissions.reminders.confirm',
            $this->reminder->link_expires_at,
            [
                'submission' => $this->reminder->submission,
                'reminder' => $this->reminder,
            ],
        );
    }
}
