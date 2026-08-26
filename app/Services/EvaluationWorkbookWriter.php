<?php

namespace App\Services;

use App\Enums\EvaluationExportScope;
use App\Exceptions\EvaluationExportSourceInvalid;
use App\Models\Evaluation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

final class EvaluationWorkbookWriter
{
    private Style $headerStyle;

    private Style $bodyStyle;

    public function __construct(private EvaluationDraftCalculator $calculator)
    {
        $this->headerStyle = (new Style)
            ->setFontBold()
            ->setFontColor('FFFFFF')
            ->setBackgroundColor('FF1B5E20')
            ->setShouldWrapText();
        $this->bodyStyle = (new Style)->setShouldWrapText();
    }

    /** @return array{evaluation_count:int,revision_count:int,criterion_count:int,reopening_count:int} */
    public function write(
        string $outputPath,
        EvaluationExportScope $scope = EvaluationExportScope::AllRevisions,
    ): array {
        return match ($scope) {
            EvaluationExportScope::CurrentRevisions => $this->writeCurrentRevisions($outputPath),
            EvaluationExportScope::AllRevisions => $this->writeAllRevisions($outputPath),
        };
    }

    /** @return array{evaluation_count:int,revision_count:int,criterion_count:int,reopening_count:int} */
    private function writeAllRevisions(string $outputPath): array
    {
        $writer = new Writer;
        $writer->openToFile($outputPath);
        $counts = [
            'evaluation_count' => 0,
            'revision_count' => 0,
            'criterion_count' => 0,
            'reopening_count' => 0,
        ];

        try {
            $sheets = $this->createHistoricalSheets($writer);
            Evaluation::query()
                ->with([
                    'judgeAssignment.submissionVersion',
                    'rubricVersion',
                    'revisions.subjectJudgeProfile.user:id,name',
                    'revisions.createdBy:id,name',
                    'revisions.lastSavedBy:id,name',
                    'revisions.submittedBy:id,name',
                    'revisions.scores.criterion',
                    'reopenings.sourceRevision:id,public_id,revision_number',
                    'reopenings.targetRevision:id,public_id,revision_number',
                    'reopenings.subjectJudgeProfile.user:id,name',
                    'reopenings.reopenedBy:id,name',
                ])
                ->orderBy('id')
                ->chunkById(50, function (Collection $evaluations) use ($writer, $sheets, &$counts): void {
                    foreach ($evaluations as $evaluation) {
                        $this->appendEvaluation($writer, $sheets, $evaluation, $counts);
                    }
                });

            foreach (['evaluations' => 29, 'criteria' => 17, 'reopenings' => 8] as $key => $lastColumnIndex) {
                $sheets[$key]->setAutoFilter(new AutoFilter(
                    0,
                    1,
                    $lastColumnIndex,
                    $sheets[$key]->getWrittenRowCount(),
                ));
            }

            return $counts;
        } finally {
            $writer->close();
        }
    }

    /** @return array<string, Sheet> */
    private function createHistoricalSheets(Writer $writer): array
    {
        $definitions = [
            'evaluations' => [
                'name' => 'Evaluaciones',
                'headers' => [
                    'ID propuesta', 'Folio', 'Nombre del proyecto', 'Nombre del participante',
                    'Disponibilidad de identidad en snapshot', 'Categoría', 'ID asignación', 'ID evaluación',
                    'Juez (nombre actual al exportar)', 'ID público del perfil de juez', 'Rúbrica', 'Versión de rúbrica',
                    'Número de revisión', 'Revisión vigente', 'Estado de evaluación', 'Estado de revisión',
                    'Modo de envío', 'Comentario general', 'Calificación interna (4 decimales)',
                    'Calificación presentada (2 decimales)', 'Criterios capturados', 'Criterios totales',
                    'Actor de creación (actual al exportar)', 'Último actor de guardado (actual al exportar)',
                    'Actor de envío (actual al exportar)', 'Inicio', 'Creación de revisión',
                    'Actualización de revisión', 'Envío', 'Plazo',
                ],
                'widths' => [28, 18, 42, 38, 30, 30, 28, 28, 38, 28, 48, 14, 16, 16, 18, 18, 20, 72, 24, 24, 18, 16, 38, 38, 38, 28, 28, 28, 28, 28],
            ],
            'criteria' => [
                'name' => 'Criterios',
                'headers' => [
                    'ID propuesta', 'Folio', 'ID evaluación', 'Número de revisión', 'ID perfil juez',
                    'Juez (nombre actual al exportar)', 'Código', 'Etiqueta', 'Orden', 'Peso',
                    'Puntaje mínimo', 'Puntaje máximo', 'Paso', 'Puntaje', 'Componente calculado',
                    'Comentario por criterio', 'Estado de revisión', 'Revisión vigente',
                ],
                'widths' => [28, 18, 28, 16, 28, 38, 32, 58, 12, 16, 18, 18, 14, 16, 24, 72, 18, 16],
            ],
            'reopenings' => [
                'name' => 'Reaperturas',
                'headers' => [
                    'ID propuesta', 'Folio', 'ID evaluación', 'Juez sujeto (actual al exportar)',
                    'ID perfil juez', 'Revisión fuente', 'Revisión destino',
                    'Actor real (actual al exportar)', 'Fecha de reapertura',
                ],
                'widths' => [28, 18, 28, 40, 28, 22, 22, 40, 28],
            ],
        ];

        $sheets = [];
        foreach ($definitions as $key => $definition) {
            $sheet = $sheets === [] ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
            $sheet->setName($definition['name']);
            $sheet->setSheetView((new SheetView)->setFreezeRow(2));
            foreach ($definition['widths'] as $index => $width) {
                $sheet->setColumnWidth($width, $index + 1);
            }
            $writer->addRow($this->literalRow($definition['headers'], $this->headerStyle));
            $sheets[$key] = $sheet;
        }

        return $sheets;
    }

    /** @return array{evaluation_count:int,revision_count:int,criterion_count:int,reopening_count:int} */
    private function writeCurrentRevisions(string $outputPath): array
    {
        $writer = new Writer;
        $writer->openToFile($outputPath);
        $counts = [
            'evaluation_count' => 0,
            'revision_count' => 0,
            'criterion_count' => 0,
            'reopening_count' => 0,
        ];

        try {
            $sheets = $this->createCurrentSheets($writer);
            Evaluation::query()
                ->with([
                    'judgeAssignment.submissionVersion',
                    'rubricVersion',
                    'currentRevision.subjectJudgeProfile.user:id,name',
                    'currentRevision.scores.criterion',
                    'revisions:id,evaluation_id,revision_number',
                ])
                ->orderBy('id')
                ->chunkById(50, function (Collection $evaluations) use ($writer, $sheets, &$counts): void {
                    foreach ($evaluations as $evaluation) {
                        $this->appendCurrentEvaluation($writer, $sheets, $evaluation, $counts);
                    }
                });

            foreach (['evaluations' => 12, 'criteria' => 17] as $key => $lastColumnIndex) {
                $sheets[$key]->setAutoFilter(new AutoFilter(
                    0,
                    1,
                    $lastColumnIndex,
                    $sheets[$key]->getWrittenRowCount(),
                ));
            }

            return $counts;
        } finally {
            $writer->close();
        }
    }

    /** @return array<string, Sheet> */
    private function createCurrentSheets(Writer $writer): array
    {
        $definitions = [
            'evaluations' => [
                'name' => 'Evaluaciones vigentes',
                'headers' => [
                    'Evaluación', 'Propuesta', 'Juez sujeto', 'Categoría', 'Estado', 'Revisión',
                    'Actualización', 'Estado de revisión', 'Calificación interna (4 decimales)',
                    'Calificación presentada (2 decimales)', 'Comentario general', 'ID propuesta', 'Folio',
                ],
                'widths' => [28, 24, 38, 30, 18, 14, 28, 20, 25, 27, 72, 28, 18],
            ],
            'criteria' => [
                'name' => 'Rubros vigentes',
                'headers' => [
                    'Evaluación', 'Propuesta', 'ID propuesta', 'Folio', 'Juez sujeto',
                    'ID perfil juez', 'Categoría', 'Revisión', 'Estado de revisión', 'Rúbrica',
                    'Versión de rúbrica', 'Código', 'Rubro', 'Peso', 'Puntaje',
                    'Componente calculado', 'Comentario por rubro', 'Orden',
                ],
                'widths' => [28, 24, 28, 18, 38, 28, 30, 14, 20, 48, 18, 30, 58, 16, 16, 24, 72, 12],
            ],
        ];

        $sheets = [];
        foreach ($definitions as $key => $definition) {
            $sheet = $sheets === [] ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
            $sheet->setName($definition['name']);
            $sheet->setSheetView((new SheetView)->setFreezeRow(2));
            foreach ($definition['widths'] as $index => $width) {
                $sheet->setColumnWidth($width, $index + 1);
            }
            $writer->addRow($this->literalRow($definition['headers'], $this->headerStyle));
            $sheets[$key] = $sheet;
        }

        return $sheets;
    }

    /**
     * @param  array<string, Sheet>  $sheets
     * @param  array{evaluation_count:int,revision_count:int,criterion_count:int,reopening_count:int}  $counts
     */
    private function appendCurrentEvaluation(Writer $writer, array $sheets, Evaluation $evaluation, array &$counts): void
    {
        $assignment = $evaluation->judgeAssignment;
        $rubric = $evaluation->rubricVersion;
        $revision = $evaluation->currentRevision;
        if (! $assignment || ! $assignment->submissionVersion || ! $rubric || ! $revision) {
            throw new EvaluationExportSourceInvalid('Current evaluation export contract is incomplete.');
        }

        $latestRevisionNumber = $evaluation->revisions->max('revision_number');
        if ($revision->evaluation_id !== $evaluation->id
            || $latestRevisionNumber === null
            || $revision->revision_number !== $latestRevisionNumber
            || ! $evaluation->revisions->contains('id', $revision->id)) {
            throw new EvaluationExportSourceInvalid('Evaluation current revision contract is invalid.');
        }

        $judge = $revision->subjectJudgeProfile;
        if (! $judge || ! $judge->user || $judge->id !== $assignment->judge_profile_id) {
            throw new EvaluationExportSourceInvalid('Evaluation subject judge contract is invalid.');
        }

        $context = $this->snapshotContext($evaluation);
        $proposal = $context['folio'] !== '' ? $context['folio'] : $context['proposal_id'];
        $scores = $revision->scores->sortBy(
            fn ($score): array => [$score->criterion?->sort_order ?? PHP_INT_MAX, $score->id],
        );

        $writer->setCurrentSheet($sheets['evaluations']);
        $writer->addRow($this->literalRow([
            $evaluation->public_id,
            $proposal,
            $judge->user->name,
            $context['category'],
            $evaluation->status->label(),
            (string) $revision->revision_number,
            $this->localDate($evaluation->updated_at),
            $revision->status->label(),
            $this->decimal($revision->total_raw),
            $revision->total_raw === null ? '' : $this->calculator->display((string) $revision->total_raw),
            $revision->general_comment ?? '',
            $context['proposal_id'],
            $context['folio'],
        ]));
        $counts['evaluation_count']++;
        $counts['revision_count']++;

        foreach ($scores as $score) {
            $criterion = $score->criterion;
            if (! $criterion || $criterion->rubric_version_id !== $evaluation->rubric_version_id) {
                throw new EvaluationExportSourceInvalid('Evaluation criterion contract is invalid.');
            }

            $writer->setCurrentSheet($sheets['criteria']);
            $writer->addRow($this->literalRow([
                $evaluation->public_id,
                $proposal,
                $context['proposal_id'],
                $context['folio'],
                $judge->user->name,
                $judge->public_id,
                $context['category'],
                (string) $revision->revision_number,
                $revision->status->label(),
                $rubric->title,
                (string) $rubric->version,
                $criterion->code,
                $criterion->label,
                $this->decimal($criterion->weight),
                $this->decimal($score->score),
                $this->decimal($score->calculated_component),
                $score->comment ?? '',
                (string) $criterion->sort_order,
            ]));
            $counts['criterion_count']++;
        }
    }

    /**
     * @param  array<string, Sheet>  $sheets
     * @param  array{evaluation_count:int,revision_count:int,criterion_count:int,reopening_count:int}  $counts
     */
    private function appendEvaluation(Writer $writer, array $sheets, Evaluation $evaluation, array &$counts): void
    {
        $context = $this->snapshotContext($evaluation);
        $assignment = $evaluation->judgeAssignment;
        $rubric = $evaluation->rubricVersion;

        foreach ($evaluation->revisions->sortBy('revision_number') as $revision) {
            $scores = $revision->scores->sortBy(fn ($score): array => [$score->criterion->sort_order, $score->id]);
            $judge = $revision->subjectJudgeProfile;
            if (! $judge || ! $judge->user || $judge->id !== $assignment->judge_profile_id) {
                throw new EvaluationExportSourceInvalid('Evaluation subject judge contract is invalid.');
            }

            $writer->setCurrentSheet($sheets['evaluations']);
            $writer->addRow($this->literalRow([
                $context['proposal_id'],
                $context['folio'],
                $context['project_name'],
                $context['participant_name'],
                $context['identity_availability'],
                $context['category'],
                $assignment->public_id,
                $evaluation->public_id,
                $judge->user->name,
                $judge->public_id,
                $rubric->title,
                (string) $rubric->version,
                (string) $revision->revision_number,
                $evaluation->current_revision_id === $revision->id ? 'Sí' : 'No',
                $evaluation->status->label(),
                $revision->status->label(),
                $revision->submission_mode?->label() ?? '',
                $revision->general_comment ?? '',
                $this->decimal($revision->total_raw),
                $revision->total_raw === null ? '' : $this->calculator->display((string) $revision->total_raw),
                (string) $scores->whereNotNull('score')->count(),
                (string) $scores->count(),
                $revision->createdBy?->name ?? '',
                $revision->lastSavedBy?->name ?? '',
                $revision->submittedBy?->name ?? '',
                $this->localDate($evaluation->started_at),
                $this->localDate($revision->created_at),
                $this->localDate($revision->updated_at),
                $this->localDate($revision->submitted_at),
                $this->localDate($assignment->due_at),
            ]));
            $counts['revision_count']++;

            foreach ($scores as $score) {
                $criterion = $score->criterion;
                if (! $criterion || $criterion->rubric_version_id !== $evaluation->rubric_version_id) {
                    throw new EvaluationExportSourceInvalid('Evaluation criterion contract is invalid.');
                }
                $writer->setCurrentSheet($sheets['criteria']);
                $writer->addRow($this->literalRow([
                    $context['proposal_id'],
                    $context['folio'],
                    $evaluation->public_id,
                    (string) $revision->revision_number,
                    $judge->public_id,
                    $judge->user->name,
                    $criterion->code,
                    $criterion->label,
                    (string) $criterion->sort_order,
                    $this->decimal($criterion->weight),
                    $this->decimal($criterion->min_score),
                    $this->decimal($criterion->max_score),
                    $this->decimal($criterion->score_step),
                    $this->decimal($score->score),
                    $this->decimal($score->calculated_component),
                    $score->comment ?? '',
                    $revision->status->label(),
                    $evaluation->current_revision_id === $revision->id ? 'Sí' : 'No',
                ]));
                $counts['criterion_count']++;
            }
        }

        foreach ($evaluation->reopenings as $reopening) {
            $subject = $reopening->subjectJudgeProfile;
            if (! $subject || ! $subject->user || $subject->id !== $assignment->judge_profile_id) {
                throw new EvaluationExportSourceInvalid('Evaluation reopening subject contract is invalid.');
            }
            $writer->setCurrentSheet($sheets['reopenings']);
            $writer->addRow($this->literalRow([
                $context['proposal_id'],
                $context['folio'],
                $evaluation->public_id,
                $subject->user->name,
                $subject->public_id,
                sprintf('%d · %s', $reopening->sourceRevision->revision_number, $reopening->sourceRevision->public_id),
                sprintf('%d · %s', $reopening->targetRevision->revision_number, $reopening->targetRevision->public_id),
                $reopening->reopenedBy?->name ?? '',
                $this->localDate($reopening->reopened_at),
            ]));
            $counts['reopening_count']++;
        }

        $counts['evaluation_count']++;
    }

    /** @return array{proposal_id:string,folio:string,project_name:string,participant_name:string,identity_availability:string,category:string} */
    private function snapshotContext(Evaluation $evaluation): array
    {
        $snapshot = $evaluation->judgeAssignment->submissionVersion?->snapshot;
        $project = is_array($snapshot) ? ($snapshot['submission'] ?? null) : null;
        $participant = is_array($snapshot) ? ($snapshot['participant'] ?? null) : null;
        $category = is_array($snapshot) ? ($snapshot['category'] ?? null) : null;
        if (! is_array($snapshot)
            || ! is_array($project)
            || ! is_array($participant)
            || ! is_array($category)
            || ! is_string($project['public_id'] ?? null)
            || ! is_string($project['title'] ?? null)
            || (! is_null($project['folio'] ?? null) && ! is_string($project['folio']))
            || ! is_string($category['name'] ?? null)) {
            throw new EvaluationExportSourceInvalid('Evaluation submission snapshot is structurally invalid.');
        }

        $profile = $participant['profile'] ?? null;
        if ($profile !== null && ! is_array($profile)) {
            throw new EvaluationExportSourceInvalid('Evaluation participant snapshot is structurally invalid.');
        }
        if ($profile === null) {
            return [
                'proposal_id' => $project['public_id'],
                'folio' => $project['folio'] ?? '',
                'project_name' => $project['title'],
                'participant_name' => '',
                'identity_availability' => 'No disponible en versión enviada',
                'category' => $category['name'],
            ];
        }

        $firstNames = $this->optionalString($profile, 'first_names');
        $lastNames = $this->optionalString($profile, 'last_names');
        $fullName = preg_replace('/\s+/u', ' ', trim($firstNames.' '.$lastNames));

        return [
            'proposal_id' => $project['public_id'],
            'folio' => $project['folio'] ?? '',
            'project_name' => $project['title'],
            'participant_name' => $fullName === null ? trim($firstNames.' '.$lastNames) : $fullName,
            'identity_availability' => 'Disponible en versión enviada',
            'category' => $category['name'],
        ];
    }

    /** @param array<string, mixed> $values */
    private function optionalString(array $values, string $key): string
    {
        $value = $values[$key] ?? null;
        if ($value !== null && ! is_string($value)) {
            throw new EvaluationExportSourceInvalid('Evaluation participant name is structurally invalid.');
        }

        return $value ?? '';
    }

    private function decimal(mixed $value): string
    {
        return $value === null ? '' : bcadd((string) $value, '0', 4);
    }

    private function localDate(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        return CarbonImmutable::parse($value, 'UTC')
            ->timezone((string) config('flowerflow.timezone'))
            ->format('Y-m-d H:i:s').' '.config('flowerflow.timezone');
    }

    private function literalRow(array $values, ?Style $style = null): Row
    {
        $rowStyle = $style ?? $this->bodyStyle;

        return new Row(array_map(
            fn (mixed $value): StringCell => new StringCell((string) ($value ?? ''), $rowStyle),
            $values,
        ), $rowStyle);
    }
}
