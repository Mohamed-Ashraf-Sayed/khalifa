<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * تصدير موحّد: PDF عربي (mpdf مع دعم RTL/تشكيل الحروف) و Excel (xlsx).
 */
class ExportService
{
    /** يولّد PDF من HTML عربي ويرجّعه كتنزيل. */
    public function pdf(string $html, string $filename): \Illuminate\Http\Response
    {
        $tmp = storage_path('app/mpdf');
        if (! is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'directionality' => 'rtl',
            'default_font' => 'dejavusans',
            'tempDir' => $tmp,
            'margin_top' => 12,
            'margin_bottom' => 12,
        ]);
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->WriteHTML($html);

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * يصدّر جدولاً إلى ملف xlsx.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string|int|float|null>>  $rows
     */
    public function excel(array $headers, array $rows, string $filename, ?string $title = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);

        $r = 1;
        if ($title) {
            $sheet->setCellValue('A1', $title);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $r = 3;
        }

        $col = 1;
        foreach ($headers as $h) {
            $sheet->setCellValue([$col, $r], $h);
            $sheet->getStyle([$col, $r])->getFont()->setBold(true);
            $sheet->getStyle([$col, $r])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }
        $r++;

        foreach ($rows as $row) {
            $col = 1;
            foreach ($row as $cell) {
                $sheet->setCellValue([$col, $r], $cell);
                $col++;
            }
            $r++;
        }

        foreach (range(1, max(1, count($headers))) as $c) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        $filename = str_ends_with($filename, '.xlsx') ? $filename : $filename.'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
