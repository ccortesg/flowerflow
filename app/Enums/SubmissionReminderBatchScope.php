<?php

namespace App\Enums;

enum SubmissionReminderBatchScope: string
{
    case Single = 'single';
    case AllDrafts = 'all_drafts';
}
