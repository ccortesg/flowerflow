<?php

namespace App\Enums;

enum SubmissionFinalizationMode: string
{
    case Participant = 'participant';
    case SignedReminder = 'signed_reminder';
    case Administrative = 'administrative';
}
