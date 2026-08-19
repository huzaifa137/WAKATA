<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Builds a "candidate scores" workbook out of scanned/edited score-sheet
 * rows, same shape as wakata-scanner's XlsxWriter output:
 *
 *   School Name: ...
 *   Subject: ...
 *   (blank row)
 *   #  | Candidate Name | P1 | P2 | P3 | P4 | Average | Grade   <- only columns
 *   1  | ...            | 82 | ...                              that actually
 *                                                                 have marks
 *
 * Uses maatwebsite/excel (already a dependency of this project) instead of
 * a hand-rolled xlsx writer, so it gets the same PhpSpreadsheet styling
 * used by the other exports in App\Exports.
 */
class ScoreSheetScanExport implements FromArray, WithStyles, WithTitle, ShouldAutoSize
{
    private array $meta;
    private array $entries;
    private array $activeColumns;

    /**
     * @param array $meta ['school_name' => ..., 'subject' => ...]
     * @param array $entries List of ['candidate_name','p1','p2','p3','p4','average','grade']
     */
    public function __construct(array $meta, array $entries)
    {
        $this->meta = $meta;
        $this->entries = $entries;
        $this->activeColumns = $this->resolveActiveColumns($entries);
    }

    public function array(): array
    {
        $scoreColumns = [
            'p1' => 'P1',
            'p2' => 'P2',
            'p3' => 'P3',
            'p4' => 'P4',
            'average' => 'Average',
            'grade' => 'Grade',
        ];

        $rows = [];
        $rows[] = ['School Name:', $this->meta['school_name'] ?: '—'];
        $rows[] = ['Subject:', $this->meta['subject'] ?: '—'];
        $rows[] = [];

        $header = ['#', 'Candidate Name'];
        foreach ($this->activeColumns as $col) {
            $header[] = $scoreColumns[$col];
        }
        $rows[] = $header;

        foreach ($this->entries as $i => $entry) {
            $row = [$i + 1, $entry['candidate_name']];
            foreach ($this->activeColumns as $col) {
                $val = $entry[$col] ?? null;
                if ($col === 'grade') {
                    $row[] = $val !== null && $val !== '' ? (string) $val : '';
                } else {
                    $row[] = $val !== null && $val !== '' ? (float) $val : '';
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function title(): string
    {
        $title = $this->meta['subject'] ?: 'Scores';
        $title = preg_replace('/[:\\\\\/\?\*\[\]]/', ' ', $title);
        $title = trim($title);

        return $title === '' ? 'Scores' : mb_substr($title, 0, 31);
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        // "School Name:" / "Subject:" labels
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);

        // Locate the column-header row by content ("#" in column A) rather
        // than assuming a fixed row number. The blank spacer row added in
        // array() isn't guaranteed to render as its own Excel row on every
        // engine/version, which previously caused this styling to land one
        // row too low — coloring the first data row instead of the header.
        $headerRow = null;
        for ($r = 1; $r <= $lastRow; $r++) {
            if ((string) $sheet->getCell("A{$r}")->getValue() === '#') {
                $headerRow = $r;
                break;
            }
        }
        if ($headerRow === null) {
            $headerRow = 4; // fallback, matches the intended layout
        }

        // Column header row
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '043AA1'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastRow}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        return [];
    }

    /**
     * Only includes a paper/score column if at least one entry has a value
     * for it, so a "Grade only" sheet doesn't render four empty P columns.
     */
    private function resolveActiveColumns(array $entries): array
    {
        $keys = ['p1', 'p2', 'p3', 'p4', 'average', 'grade'];

        return array_values(array_filter($keys, function ($key) use ($entries) {
            foreach ($entries as $e) {
                if (isset($e[$key]) && $e[$key] !== null && $e[$key] !== '') {
                    return true;
                }
            }
            return false;
        }));
    }
}