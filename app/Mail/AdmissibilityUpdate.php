<?php

namespace App\Mail;

use App\Models\EligibilityReview;
use App\Support\ConfiguresTransactionalMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdmissibilityUpdate extends Mailable implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use ConfiguresTransactionalMail, Queueable, SerializesModels;

    public function __construct(public EligibilityReview $review, public string $kind)
    {
        $this->configureTransactionalMail();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address(config('flowerflow.mail.reply_to'), config('flowerflow.mail.reply_to_name'))],
            subject: $this->subjectLine()
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.admissibility-update',
            text: 'mail.admissibility-update-text',
            with: ['copy' => $this->copy()]
        );
    }

    private function subjectLine(): string
    {
        $folio = $this->review->submission->folio;

        return match ($this->kind) {
            'clarification_requested' => "Aclaración solicitada · $folio",
            'residency_requested' => "Comprobante de residencia solicitado · $folio",
            'response_received' => "Respuesta recibida · $folio",
            'admitted' => "Propuesta admitida · $folio",
            'not_admitted' => "Resolución de admisibilidad · $folio",
            default => "Actualización de tu propuesta · $folio",
        };
    }

    private function copy(): array
    {
        return match ($this->kind) {
            'clarification_requested' => [
                'kicker' => 'Revisión de participación',
                'title' => 'Necesitamos una aclaración',
                'body' => 'El equipo de revisión registró una solicitud de aclaración. Ingresa a Flower Flow para consultar el detalle y responder de forma segura.',
                'button' => 'Consultar aclaración',
            ],
            'residency_requested' => [
                'kicker' => 'Verificación privada',
                'title' => 'Necesitamos un comprobante de residencia',
                'body' => 'Se solicitó documentación para verificar la residencia de una persona vinculada con tu propuesta. Carga el archivo únicamente dentro de Flower Flow.',
                'button' => 'Atender solicitud',
            ],
            'response_received' => [
                'kicker' => 'Confirmación',
                'title' => 'Recibimos tu respuesta',
                'body' => 'Tu respuesta quedó registrada y estará disponible para el equipo de revisión. Conserva tu folio para cualquier seguimiento.',
                'button' => 'Ver revisión',
            ],
            'admitted' => [
                'kicker' => 'Resolución de admisibilidad',
                'title' => 'Tu propuesta fue admitida',
                'body' => 'La propuesta puede avanzar a una futura fase de evaluación. Esta resolución no significa que sea ganadora ni implica una verificación de residencia cuando no fue solicitada.',
                'button' => 'Consultar resolución',
            ],
            'not_admitted' => [
                'kicker' => 'Resolución de admisibilidad',
                'title' => 'Tu propuesta no fue admitida',
                'body' => 'La revisión administrativa concluyó. Ingresa a Flower Flow para consultar el motivo registrado para tu propuesta.',
                'button' => 'Consultar resolución',
            ],
            default => [
                'kicker' => 'Actualización',
                'title' => 'Hay una actualización en tu propuesta',
                'body' => 'Ingresa a Flower Flow para consultar el detalle de manera segura.',
                'button' => 'Ver propuesta',
            ],
        };
    }
}
