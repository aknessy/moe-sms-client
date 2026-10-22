<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * One way of handing a table back as a spreadsheet, so every report that
 * offers a download produces the same thing: bold headings held in view, and
 * columns wide enough to read.
 */
class SpreadsheetExport
{
    /**
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public static function download(string $filename, string $sheetTitle, array $headings, iterable $rows): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Excel refuses some characters in a tab name and caps it at 31.
        $sheet->setTitle(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $sheetTitle), 0, 31));

        $sheet->fromArray($headings, null, 'A1');
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);

        $line = 2;
        foreach ($rows as $row) {
            $sheet->fromArray(array_values($row), null, "A{$line}");
            $line++;
        }

        foreach (range('A', $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Keeps the headings in view when the sheet runs long.
        $sheet->freezePane('A2');

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** `deceased-officers-2025-2026-10-22.xlsx` */
    public static function filename(string $stem, ?string $qualifier = null): string
    {
        return collect([$stem, $qualifier, now()->format('Y-m-d')])
            ->filter()
            ->implode('-') . '.xlsx';
    }
}
