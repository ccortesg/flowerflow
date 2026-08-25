<?php

namespace App\Services;

use App\Models\BlindReviewPackage;
use App\Models\JudgeAssignment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class JudgeProjectWorkbookWriter
{
    private const GREEN = '167C5B';

    private const INK = '17352F';

    private const CREAM = 'FFFDF5';

    public function write(JudgeAssignment $assignment, BlindReviewPackage $package, string $path): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Flower Flow')
            ->setTitle('Proyecto asignado '.$assignment->public_id)
            ->setSubject('Paquete ciego para evaluación');

        $project = $spreadsheet->getActiveSheet();
        $project->setTitle('Proyecto');
        $links = $spreadsheet->createSheet()->setTitle('Enlaces');
        $attachments = $spreadsheet->createSheet()->setTitle('Anexos');

        $payload = $package->payload;
        $this->prepare($project, 'Proyecto asignado');
        $this->tableHeader($project, ['A5' => 'Campo', 'B5' => 'Contenido']);
        $projectRows = [
            ['Asignación', $assignment->public_id],
            ['Categoría', (string) data_get($payload, 'category.name')],
            ['Modalidad', data_get($payload, 'submission.participation_type') === 'team' ? 'Equipo' : 'Individual'],
            ['Nombre del proyecto', (string) data_get($payload, 'submission.title')],
            ['Resumen', (string) data_get($payload, 'submission.summary')],
            ['Descripción', (string) data_get($payload, 'submission.description_text')],
        ];
        foreach ($projectRows as $offset => [$label, $value]) {
            $row = 6 + $offset;
            $this->text($project, 'A'.$row, $label);
            $this->text($project, 'B'.$row, $value);
            $project->getRowDimension($row)->setRowHeight($row >= 10 ? 90 : -1);
        }
        $project->getColumnDimension('A')->setWidth(25);
        $project->getColumnDimension('B')->setWidth(92);
        $project->getStyle('A6:A11')->getFont()->setBold(true)->getColor()->setARGB(self::INK);
        $project->getStyle('B6:B11')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);

        $this->prepare($links, 'Enlaces externos');
        $this->tableHeader($links, ['A5' => 'Tipo', 'B5' => 'Dominio', 'C5' => 'URL']);
        foreach (data_get($payload, 'external_links', []) as $index => $link) {
            $row = 6 + $index;
            $this->text($links, 'A'.$row, $link['kind'] === 'youtube' ? 'Video del proyecto' : 'Carpeta pública');
            $this->text($links, 'B'.$row, $link['normalized_host']);
            $this->text($links, 'C'.$row, $link['url']);
            $links->getCell('C'.$row)->getHyperlink()->setUrl($link['url']);
        }
        if (data_get($payload, 'external_links', []) === []) {
            $this->text($links, 'A6', 'Sin enlaces externos.');
            $links->mergeCells('A6:C6');
        }
        $links->getColumnDimension('A')->setWidth(25);
        $links->getColumnDimension('B')->setWidth(30);
        $links->getColumnDimension('C')->setWidth(75);

        $this->prepare($attachments, 'Anexos evaluables');
        $this->tableHeader($attachments, ['A5' => 'Etiqueta', 'B5' => 'Clase', 'C5' => 'Formato', 'D5' => 'Tamaño', 'E5' => 'Descarga privada']);
        foreach ($package->files as $index => $file) {
            $row = 6 + $index;
            $downloadUrl = route('judge.assignments.packages.files.download', [$assignment, $file]);
            $this->text($attachments, 'A'.$row, $file->neutral_label);
            $this->text($attachments, 'B'.$row, $file->file_class->label());
            $this->text($attachments, 'C'.$row, $file->expected_extension);
            $this->text($attachments, 'D'.$row, number_format($file->expected_size_bytes / 1024, 1).' KiB');
            $this->text($attachments, 'E'.$row, 'Descargar anexo autenticado');
            $attachments->getCell('E'.$row)->getHyperlink()->setUrl($downloadUrl);
        }
        if ($package->files->isEmpty()) {
            $this->text($attachments, 'A6', 'Sin anexos capturados.');
            $attachments->mergeCells('A6:E6');
        }
        foreach (['A' => 28, 'B' => 22, 'C' => 16, 'D' => 16, 'E' => 36] as $column => $width) {
            $attachments->getColumnDimension($column)->setWidth($width);
        }

        foreach ([$project, $links, $attachments] as $sheet) {
            $lastRow = max(6, $sheet->getHighestDataRow());
            $lastColumn = match ($sheet->getTitle()) {
                'Proyecto' => 'B',
                'Enlaces' => 'C',
                'Anexos' => 'E',
            };
            $sheet->freezePane('A6');
            $sheet->setAutoFilter("A5:{$lastColumn}5");
            $sheet->getStyle("A5:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('DCE8E2');
            $sheet->getStyle("A6:{$lastColumn}{$lastRow}")->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
            $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                ->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
            $sheet->getPageMargins()->setTop(0.5)->setRight(0.35)->setBottom(0.5)->setLeft(0.35);
            $sheet->getHeaderFooter()->setOddFooter('&LFlower Flow — Paquete ciego&CConfidencial&RPágina &P de &N');
            $sheet->setSelectedCell('A6');
        }

        $spreadsheet->setActiveSheetIndex(0);
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    private function prepare(Worksheet $sheet, string $title): void
    {
        $sheet->setShowGridlines(false);
        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('A2:E2');
        $this->text($sheet, 'A2', $title);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(18)->getColor()->setARGB(self::INK);
        $this->text($sheet, 'A3', 'Información obtenida exclusivamente del paquete ciego inmutable.');
        $sheet->mergeCells('A3:E3');
        $sheet->getStyle('A3')->getFont()->setItalic(true)->getColor()->setARGB('5F6F69');
        $this->drawing($sheet, public_path('assets/flowerflow/logo_flowerflow_transparente.png'), 'A1', 78, 'Flower Flow');
        $this->drawing($sheet, public_path('assets/flowerflow/logo_florecehermosillo_transparente.png'), 'D1', 42, 'Florece Hermosillo');
        $sheet->getRowDimension(1)->setRowHeight(60);
        $sheet->getRowDimension(2)->setRowHeight(28);
    }

    /** @param array<string,string> $cells */
    private function tableHeader(Worksheet $sheet, array $cells): void
    {
        foreach ($cells as $coordinate => $value) {
            $this->text($sheet, $coordinate, $value);
        }
        $last = array_key_last($cells);
        $sheet->getStyle('A5:'.$last)->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
        $sheet->getStyle('A5:'.$last)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::GREEN);
        $sheet->getStyle('A5:'.$last)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(5)->setRowHeight(24);
    }

    private function text(Worksheet $sheet, string $coordinate, string $value): void
    {
        $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_STRING);
    }

    private function drawing(Worksheet $sheet, string $path, string $coordinate, int $height, string $description): void
    {
        $drawing = new Drawing;
        $drawing->setName($description)->setDescription($description)->setPath($path)
            ->setHeight($height)->setCoordinates($coordinate)->setWorksheet($sheet);
    }
}
