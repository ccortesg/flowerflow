<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

trait ConfiguresTransactionalMail
{
    public int $tries;

    public int $timeout;

    /** @var array<int, int> */
    public array $backoff;

    protected function configureTransactionalMail(): void
    {
        $this->tries = config('flowerflow.mail.tries');
        $this->timeout = config('flowerflow.mail.timeout');
        $this->backoff = config('flowerflow.mail.backoff');
        $this->onConnection(config('flowerflow.mail.queue_connection'));
        $this->onQueue(config('flowerflow.mail.queue'));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Un correo transaccional agotó sus reintentos.', [
            'mail_type' => static::class,
            'exception_class' => $exception::class,
        ]);
    }
}
