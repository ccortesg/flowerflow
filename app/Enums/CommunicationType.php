<?php

namespace App\Enums;

enum CommunicationType: string
{
    case AccountEmailVerification = 'account.email_verification';
    case AccountPasswordReset = 'account.password_reset';
    case JudgeAccountSetup = 'judge.account_setup';
    case JudgeEmailVerification = 'judge.email_verification';
    case JudgeAccountStatus = 'judge.account_status';
    case JudgeAssignmentCreated = 'judge.assignment_created';
    case SubmissionReceived = 'submission.received';
    case SubmissionAdministrativelyFinalized = 'submission.administratively_finalized';
    case SubmissionDraftReminder = 'submission.draft_reminder';
    case AdmissibilityUpdate = 'admissibility.update';

    public function label(): string
    {
        return match ($this) {
            self::AccountEmailVerification => 'Verificación de correo',
            self::AccountPasswordReset => 'Restablecimiento de contraseña',
            self::JudgeAccountSetup => 'Configuración de cuenta de juez',
            self::JudgeEmailVerification => 'Verificación de correo de juez',
            self::JudgeAccountStatus => 'Estado de cuenta de juez',
            self::JudgeAssignmentCreated => 'Nueva asignación de evaluación',
            self::SubmissionReceived => 'Acuse de propuesta',
            self::SubmissionAdministrativelyFinalized => 'Registro administrativo de propuesta',
            self::SubmissionDraftReminder => 'Recordatorio de propuesta en borrador',
            self::AdmissibilityUpdate => 'Actualización de admisibilidad',
        };
    }
}
