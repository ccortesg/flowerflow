<?php

namespace App\Enums;

enum SubmissionReminderStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
