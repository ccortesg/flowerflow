<?php

namespace App\Mail;

use App\Models\Submission;
use App\Support\ConfiguresTransactionalMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubmissionReceived extends Mailable implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use ConfiguresTransactionalMail, Queueable, SerializesModels;

    public function __construct(public Submission $submission)
    {
        $this->configureTransactionalMail();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address(config('flowerflow.mail.reply_to'), config('flowerflow.mail.reply_to_name'))],
            subject: 'Propuesta recibida · '.$this->submission->folio
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.submission-received',
            text: 'mail.submission-received-text'
        );
    }
}
