<?php

namespace App\Services;

use App\Models\Submission;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

final class SubmissionWorkbookWriter
{
    private Style $headerStyle;

    private Style $bodyStyle;

    private Style $linkStyle;

    public function __construct()
    {
        $this->headerStyle = (new Style)
            ->setFontBold()
            ->setFontColor('FFFFFF')
            ->setBackgroundColor('FF1B5E20')
            ->setShouldWrapText();
        $this->bodyStyle = (new Style)->setShouldWrapText();
        $this->linkStyle = (new Style)
            ->setFontColor('1565C0')
            ->setFontUnderline();
    }

    /**
     * @return array{proposal_count:int,contact_count:int,team_member_count:int,file_count:int,external_link_count:int}
     */
    public function write(string $outputPath): array
    {
        $writer = new Writer;
        $writer->openToFile($outputPath);

        try {
            $sheets = $this->createSheets($writer);
            $counts = [
                'proposal_count' => 0,
                'contact_count' => 0,
                'team_member_count' => 0,
                'file_count' => 0,
                'external_link_count' => 0,
            ];

            Submission::query()
                ->whereIn('status', ['draft', 'submitted'])
                ->with([
                    'competition',
                    'category',
                    'user.profile',
                    'team.members',
                    'files',
                    'externalLinks',
                    'versions',
                ])
                ->orderBy('id')
                ->chunkById(100, function (Collection $submissions) use ($writer, $sheets, &$counts): void {
                    foreach ($submissions as $submission) {
                        $this->appendSubmission($writer, $sheets, $submission, $counts);
                    }
                });

            foreach ([
                'proposals' => 12,
                'contacts' => 7,
                'members' => 5,
                'files' => 7,
                'links' => 4,
            ] as $key => $lastColumnIndex) {
                $sheets[$key]->setAutoFilter(new AutoFilter(0, 1, $lastColumnIndex, $sheets[$key]->getWrittenRowCount()));
            }

            return $counts;
        } finally {
            $writer->close();
        }
    }

    /**
     * @return array<string, Sheet>
     */
    private function createSheets(Writer $writer): array
    {
        $definitions = [
            'proposals' => [
                'name' => 'Propuestas',
                'headers' => ['ID propuesta', 'Folio', 'Estado', 'Convocatoria', 'Categoría', 'Modalidad', 'Título', 'Resumen', 'Descripción', 'Creada', 'Actualizada', 'Enviada', 'Fuente'],
                'widths' => [26, 18, 13, 30, 28, 16, 36, 48, 72, 23, 23, 23, 20],
            ],
            'contacts' => [
                'name' => 'Contactos',
                'headers' => ['ID propuesta', 'Folio', 'Nombres', 'Apellidos', 'Correo', 'Celular', 'Colonia', 'WhatsApp'],
                'widths' => [26, 18, 28, 28, 38, 20, 32, 14],
            ],
            'members' => [
                'name' => 'Integrantes',
                'headers' => ['ID propuesta', 'Folio', 'Equipo', 'Nombre', 'Correo', 'Representante'],
                'widths' => [26, 18, 32, 36, 38, 18],
            ],
            'files' => [
                'name' => 'Archivos',
                'headers' => ['ID propuesta', 'Folio', 'Tipo', 'Nombre', 'MIME', 'Extensión', 'Tamaño bytes', 'Descarga'],
                'widths' => [26, 18, 18, 48, 30, 14, 18, 22],
            ],
            'links' => [
                'name' => 'Enlaces externos',
                'headers' => ['ID propuesta', 'Folio', 'Tipo', 'URL', 'Host'],
                'widths' => [26, 18, 22, 64, 32],
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
     * @param  array{proposal_count:int,contact_count:int,team_member_count:int,file_count:int,external_link_count:int}  $counts
     */
    private function appendSubmission(Writer $writer, array $sheets, Submission $submission, array &$counts): void
    {
        $data = $this->exportData($submission);
        $project = $data['submission'];
        $competition = $data['competition'];
        $category = $data['category'];
        $participant = $data['participant'];
        $profile = is_array($participant['profile'] ?? null) ? $participant['profile'] : [];
        $proposalId = (string) ($project['public_id'] ?? $submission->public_id);
        $folio = (string) ($project['folio'] ?? $submission->folio ?? '');

        $writer->setCurrentSheet($sheets['proposals']);
        $writer->addRow($this->literalRow([
            $proposalId,
            $folio,
            $submission->statusLabel(),
            $competition['name'] ?? '',
            $category['name'] ?? '',
            ($project['participation_type'] ?? $submission->participation_type) === 'team' ? 'Equipo' : 'Individual',
            $project['title'] ?? '',
            $project['summary'] ?? '',
            $project['description_text'] ?? '',
            $this->localDate($submission->created_at),
            $this->localDate($submission->updated_at),
            $this->localDate($project['submitted_at'] ?? null),
            $submission->status === 'submitted' ? 'Versión enviada' : 'Borrador actual',
        ]));
        $counts['proposal_count']++;

        $writer->setCurrentSheet($sheets['contacts']);
        $writer->addRow($this->literalRow([
            $proposalId,
            $folio,
            $profile['first_names'] ?? '',
            $profile['last_names'] ?? '',
            $participant['email'] ?? '',
            $profile['mobile_e164'] ?? '',
            $profile['neighborhood'] ?? '',
            ($profile['whatsapp_opt_in'] ?? false) ? 'Sí' : 'No',
        ]));
        $counts['contact_count']++;

        $team = is_array($data['team']) ? $data['team'] : null;
        foreach (($team['members'] ?? []) as $member) {
            $writer->setCurrentSheet($sheets['members']);
            $writer->addRow($this->literalRow([
                $proposalId,
                $folio,
                $team['name'] ?? '',
                $member['full_name'] ?? '',
                $member['email'] ?? '',
                ($member['is_representative'] ?? false) ? 'Sí' : 'No',
            ]));
            $counts['team_member_count']++;
        }

        $currentFiles = $submission->files->keyBy('public_id');
        foreach ($data['files'] as $fileData) {
            $file = $currentFiles->get($fileData['public_id'] ?? null);
            $writer->setCurrentSheet($sheets['files']);
            $writer->addRow(new Row([
                ...$this->literalCells([
                    $proposalId,
                    $folio,
                    ($fileData['kind'] ?? '') === 'editor_image' ? 'Imagen del editor' : 'Documento',
                    $fileData['original_name'] ?? '',
                    $fileData['mime_type'] ?? '',
                    $fileData['extension'] ?? '',
                ]),
                new NumericCell((int) ($fileData['size_bytes'] ?? 0), $this->bodyStyle),
                $file
                    ? new FormulaCell($this->downloadFormula($submission, $file), $this->linkStyle, 'Descargar')
                    : new StringCell('No disponible', $this->bodyStyle),
            ], $this->bodyStyle));
            $counts['file_count']++;
        }

        foreach ($data['external_links'] as $link) {
            $writer->setCurrentSheet($sheets['links']);
            $writer->addRow($this->literalRow([
                $proposalId,
                $folio,
                ($link['kind'] ?? '') === 'youtube' ? 'Video de YouTube' : 'Carpeta pública',
                $link['url'] ?? '',
                $link['normalized_host'] ?? '',
            ]));
            $counts['external_link_count']++;
        }
    }

    /**
     * @return array{submission:array<string,mixed>,competition:array<string,mixed>,category:array<string,mixed>,participant:array<string,mixed>,team:?array<string,mixed>,files:array<int,array<string,mixed>>,external_links:array<int,array<string,mixed>>}
     */
    private function exportData(Submission $submission): array
    {
        if ($submission->status === 'submitted') {
            $snapshot = $submission->versions->sortByDesc('version')->first()?->snapshot;
            if (! is_array($snapshot) || ! is_array($snapshot['submission'] ?? null)) {
                throw new RuntimeException('Submitted proposal has no immutable snapshot.');
            }

            return [
                'submission' => $snapshot['submission'],
                'competition' => is_array($snapshot['competition'] ?? null) ? $snapshot['competition'] : [],
                'category' => is_array($snapshot['category'] ?? null) ? $snapshot['category'] : [],
                'participant' => is_array($snapshot['participant'] ?? null) ? $snapshot['participant'] : [],
                'team' => is_array($snapshot['team'] ?? null) ? $snapshot['team'] : null,
                'files' => is_array($snapshot['files'] ?? null) ? $snapshot['files'] : [],
                'external_links' => is_array($snapshot['external_links'] ?? null) ? $snapshot['external_links'] : [],
            ];
        }

        return [
            'submission' => $submission->only([
                'public_id', 'folio', 'participation_type', 'title', 'summary', 'description_text', 'submitted_at',
            ]),
            'competition' => $submission->competition?->only(['public_id', 'slug', 'name']) ?? [],
            'category' => $submission->category?->only(['public_id', 'slug', 'name']) ?? [],
            'participant' => [
                'public_id' => $submission->user?->public_id,
                'email' => $submission->user?->email,
                'profile' => $submission->user?->profile?->only([
                    'first_names', 'last_names', 'mobile_e164', 'whatsapp_opt_in', 'neighborhood',
                ]),
            ],
            'team' => $submission->team ? [
                'name' => $submission->team->name,
                'members' => $submission->team->members->map->only([
                    'full_name', 'email', 'is_representative',
                ])->all(),
            ] : null,
            'files' => $submission->files->map->only([
                'public_id', 'kind', 'original_name', 'mime_type', 'extension', 'size_bytes',
            ])->all(),
            'external_links' => $submission->externalLinks->map->only([
                'kind', 'url', 'normalized_host',
            ])->all(),
        ];
    }

    private function localDate(mixed $date): string
    {
        if (blank($date)) {
            return '';
        }

        return CarbonImmutable::parse($date, 'UTC')
            ->timezone(config('flowerflow.timezone'))
            ->format('Y-m-d H:i:s').' '.config('flowerflow.timezone');
    }

    private function downloadFormula(Submission $submission, mixed $file): string
    {
        $relativeUrl = route('submissions.files.download', [$submission, $file], false);
        $url = rtrim((string) config('flowerflow.canonical_url'), '/').$relativeUrl;

        return sprintf('=HYPERLINK("%s","Descargar")', str_replace('"', '""', $url));
    }

    private function literalRow(array $values, ?Style $style = null): Row
    {
        return new Row($this->literalCells($values, $style), $style ?? $this->bodyStyle);
    }

    /**
     * @return array<int, StringCell>
     */
    private function literalCells(array $values, ?Style $style = null): array
    {
        return array_map(
            fn (mixed $value): StringCell => new StringCell((string) ($value ?? ''), $style ?? $this->bodyStyle),
            $values
        );
    }
}
