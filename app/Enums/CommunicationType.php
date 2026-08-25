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
    case JudgeAssignmentBulkCreated = 'judge.assignment_bulk_created';
    case JudgeConflictDeclared = 'judge.conflict_declared';
    case JudgeConflictResolved = 'judge.conflict_resolved';
    case EvaluationSubmitted = 'evaluation.submitted';
    case EvaluationReopened = 'evaluation.reopened';
    case EvaluationCloseDigest = 'evaluation.close_digest';
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
            self::JudgeAssignmentBulkCreated => 'Nuevas asignaciones de evaluación',
            self::JudgeConflictDeclared => 'Conflicto de evaluación declarado',
            self::JudgeConflictResolved => 'Conflicto de evaluación resuelto',
            self::EvaluationSubmitted => 'Evaluación enviada',
            self::EvaluationReopened => 'Evaluación reabierta',
            self::EvaluationCloseDigest => 'Resumen de cierre de evaluación',
            self::SubmissionReceived => 'Acuse de propuesta',
            self::SubmissionAdministrativelyFinalized => 'Registro administrativo de propuesta',
            self::SubmissionDraftReminder => 'Recordatorio de propuesta en borrador',
            self::AdmissibilityUpdate => 'Actualización de admisibilidad',
        };
    }
}
