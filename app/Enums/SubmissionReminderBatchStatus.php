<?php

namespace App\Enums;

enum SubmissionReminderBatchStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case CompletedWithFailures = 'completed_with_failures';
    case Failed = 'failed';
}
