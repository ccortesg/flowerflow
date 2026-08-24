<?php

namespace App\Services;

use App\Models\Submission;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

final class SubmissionContactsWorkbookWriter
{
    private Style $headerStyle;

    private Style $bodyStyle;

    public function __construct()
    {
        $this->headerStyle = (new Style)
            ->setFontBold()
            ->setFontColor('FFFFFF')
            ->setBackgroundColor('FF1B5E20')
            ->setShouldWrapText();
        $this->bodyStyle = (new Style)->setShouldWrapText();
    }

    /**
     * @return array{proposal_count:int,contact_count:int,team_member_count:int,file_count:int,external_link_count:int}
     */
    public function write(string $outputPath): array
    {
        $writer = new Writer;
        $writer->openToFile($outputPath);

        $count = 0;

        try {
            $sheet = $writer->getCurrentSheet();
            $sheet->setName('Contactos');
            $sheet->setSheetView((new SheetView)->setFreezeRow(2));

            foreach ([38, 42, 24, 48, 96] as $index => $width) {
                $sheet->setColumnWidth($width, $index + 1);
            }

            $writer->addRow($this->literalRow([
                'Nombre completo',
                'Correo electrónico',
                'Teléfono de contacto',
                'Nombre del proyecto',
                'Descripción del proyecto',
            ], $this->headerStyle));

            Submission::query()
                ->where('status', 'submitted')
                ->with('versions')
                ->orderBy('id')
                ->chunkById(100, function (Collection $submissions) use ($writer, &$count): void {
                    foreach ($submissions as $submission) {
                        $writer->addRow($this->literalRow($this->contactRow($submission)));
                        $count++;
                    }
                });

            $sheet->setAutoFilter(new AutoFilter(0, 1, 4, $sheet->getWrittenRowCount()));

            return [
                'proposal_count' => $count,
                'contact_count' => $count,
                'team_member_count' => 0,
                'file_count' => 0,
                'external_link_count' => 0,
            ];
        } finally {
            $writer->close();
        }
    }

    /** @return list<string> */
    private function contactRow(Submission $submission): array
    {
        $snapshot = $submission->versions->sortByDesc('version')->first()?->snapshot;
        $project = is_array($snapshot) ? ($snapshot['submission'] ?? null) : null;
        $participant = is_array($snapshot) ? ($snapshot['participant'] ?? null) : null;

        if (
            ! is_array($snapshot)
            || ! is_array($project)
            || ! is_array($participant)
            || ! array_key_exists('title', $project)
            || ! is_string($project['title'])
            || ! array_key_exists('description_text', $project)
            || ! is_string($project['description_text'])
            || ! array_key_exists('email', $participant)
            || ! is_string($participant['email'])
        ) {
            throw new RuntimeException('Submitted proposal has an invalid immutable snapshot.');
        }

        $profile = $participant['profile'] ?? null;
        if ($profile !== null && ! is_array($profile)) {
            throw new RuntimeException('Submitted proposal has an invalid immutable participant profile.');
        }

        $profile ??= [];
        $firstNames = $this->optionalString($profile, 'first_names');
        $lastNames = $this->optionalString($profile, 'last_names');
        $mobile = $this->optionalString($profile, 'mobile_e164');
        $fullName = preg_replace('/\s+/u', ' ', trim("{$firstNames} {$lastNames}"));

        return [
            $fullName === null ? trim("{$firstNames} {$lastNames}") : $fullName,
            $participant['email'],
            $mobile,
            $project['title'],
            $project['description_text'],
        ];
    }

    /** @param array<string, mixed> $values */
    private function optionalString(array $values, string $key): string
    {
        $value = $values[$key] ?? null;
        if ($value !== null && ! is_string($value)) {
            throw new RuntimeException('Submitted proposal has an invalid immutable participant profile.');
        }

        return $value ?? '';
    }

    private function literalRow(array $values, ?Style $style = null): Row
    {
        $rowStyle = $style ?? $this->bodyStyle;

        return new Row(array_map(
            fn (mixed $value): StringCell => new StringCell((string) ($value ?? ''), $rowStyle),
            $values
        ), $rowStyle);
    }
}
