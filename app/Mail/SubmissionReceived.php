<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubmissionReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Submission $submission) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address(config('flowerflow.mail.reply_to'), config('flowerflow.mail.reply_to_name'))],
            subject: 'Propuesta recibida · '.$this->submission->folio
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.submission-received');
    }
}
