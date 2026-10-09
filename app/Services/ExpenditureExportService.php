<?php

namespace App\Services;

use App\Models\Expenditure;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExpenditureExportService
{
    /**
     * Menghasilkan Spreadsheet dengan pembagian tab per bulan untuk data pencairan belanja (SPD).
     *
     * @param Collection<int, Expenditure> $expenditures
     * @return Spreadsheet
     */
    public function generateSpdDisbursementSpreadsheet(Collection $expenditures): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        // Jika data kosong, buat 1 sheet kosong dengan pesan informasi
        if ($expenditures->isEmpty()) {
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Data Kosong');
            $this->setupHeaderAndColumns($sheet, 'Semua Periode');
            $sheet->setCellValue('A6', 'Tidak ada data pencairan dana belanja yang ditemukan untuk filter yang dipilih.');
            $sheet->mergeCells('A6:U6');
            $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            return $spreadsheet;
        }

        // Kelompokkan data per Bulan (berdasarkan spd_date, fallback ke date)
        $grouped = $expenditures->groupBy(function (Expenditure $item) {
            $date = $item->spd_date ?? $item->date;
            if (!$date) {
                return 'Tanpa Tanggal';
            }
            return Carbon::parse($date)->locale('id')->translatedFormat('F Y');
        });

        $sheetIndex = 0;
        foreach ($grouped as $monthName => $items) {
            if ($sheetIndex === 0) {
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $sheet = $spreadsheet->createSheet();
            }

            // Bersihkan nama tab agar tidak melebihi 31 karakter dan tidak mengandung karakter dilarang
            $cleanTitle = mb_substr(str_replace(['*', ':', '/', '\\', '?', '[', ']'], '', $monthName), 0, 31);
            $sheet->setTitle($cleanTitle);

            $this->populateMonthSheet($sheet, $monthName, $items);
            $sheetIndex++;
        }

        // Kembalikan kursor ke sheet pertama
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Mengisi konten satu sheet untuk bulan tertentu.
     */
    protected function populateMonthSheet($sheet, string $monthName, Collection $items): void
    {
        $this->setupHeaderAndColumns($sheet, $monthName);

        $row = 6;
        $no = 1;

        foreach ($items as $item) {
            $dateFormatted = $item->date ? Carbon::parse($item->date)->format('d/m/Y') : '-';
            
            // Nama Pihak Ketiga
            $recipientName = $this->resolveRecipientName($item);

            // Gabungkan kode dan nama rekening belanja (Opsi B: multi-rekening di-newline dalam 1 sel)
            $kodeRekening = $item->details
                ->map(fn($d) => $d->accountCode?->code)
                ->filter()
                ->unique()
                ->implode("\n");

            $uraianRekening = $item->details
                ->map(fn($d) => $d->accountCode?->name)
                ->filter()
                ->unique()
                ->implode("\n");

            // Nominal Bruto
            $jumlahDiminta = (float) $item->details->sum('amount');

            // Breakdown Pajak
            $ppn = (float) $item->taxes->where('tax_type', 'PPN')->sum('amount');
            $billingPpn = $item->taxes->where('tax_type', 'PPN')->pluck('billing_code')->filter()->implode(', ');

            $pph21 = (float) $item->taxes->where('tax_type', 'PPh 21')->sum('amount');
            $billingPph21 = $item->taxes->where('tax_type', 'PPh 21')->pluck('billing_code')->filter()->implode(', ');

            $pph22 = (float) $item->taxes->where('tax_type', 'PPh 22')->sum('amount');
            $billingPph22 = $item->taxes->where('tax_type', 'PPh 22')->pluck('billing_code')->filter()->implode(', ');

            $pph23 = (float) $item->taxes->where('tax_type', 'PPh 23')->sum('amount');
            $billingPph23 = $item->taxes->where('tax_type', 'PPh 23')->pluck('billing_code')->filter()->implode(', ');

            $pphFinal = (float) $item->taxes->where('tax_type', 'PPh Final')->sum('amount');
            $billingPphFinal = $item->taxes->where('tax_type', 'PPh Final')->pluck('billing_code')->filter()->implode(', ');

            $jumlahPotongan = (float) $item->taxes->sum('amount');

            // Pengisian Nilai Sel
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $monthName);
            $sheet->setCellValue("C{$row}", $dateFormatted);
            $sheet->setCellValue("D{$row}", $item->document_number ?? '-');
            $sheet->setCellValue("E{$row}", $recipientName);
            $sheet->setCellValue("F{$row}", $item->description ?? '-');
            $sheet->setCellValue("G{$row}", $kodeRekening ?: '-');
            $sheet->setCellValue("H{$row}", $uraianRekening ?: '-');
            $sheet->setCellValue("I{$row}", $jumlahDiminta);
            $sheet->setCellValue("J{$row}", $ppn);
            $sheet->setCellValue("K{$row}", $billingPpn ?: '-');
            $sheet->setCellValue("L{$row}", $pph21);
            $sheet->setCellValue("M{$row}", $billingPph21 ?: '-');
            $sheet->setCellValue("N{$row}", $pph22);
            $sheet->setCellValue("O{$row}", $billingPph22 ?: '-');
            $sheet->setCellValue("P{$row}", $pph23);
            $sheet->setCellValue("Q{$row}", $billingPph23 ?: '-');
            $sheet->setCellValue("R{$row}", $pphFinal);
            $sheet->setCellValue("S{$row}", $billingPphFinal ?: '-');
            $sheet->setCellValue("T{$row}", $jumlahPotongan);
            // Kolom U: Formula Excel (Jumlah Diminta - Jumlah Potongan)
            $sheet->setCellValue("U{$row}", "=I{$row}-T{$row}");

            // Format Alignment & Wrap Text
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
            $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
            $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);

            $sheet->getStyle("K{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("M{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("O{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("Q{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("S{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Format Angka / Accounting untuk Kolom Nominal
            $accountingFormat = '_(* #,##0_);_(* (#,##0);_(* "-"_);_(@_)';
            foreach (['I', 'J', 'L', 'N', 'P', 'R', 'T', 'U'] as $col) {
                $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode($accountingFormat);
                $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            // Atur vertical alignment ke top/center agar rapi saat baris meninggi
            $sheet->getStyle("A{$row}:U{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

            $row++;
        }

        $lastDataRow = $row - 1;
        $totalRow = $row;

        // Baris TOTAL
        $sheet->mergeCells("A{$totalRow}:H{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'TOTAL');
        $sheet->getStyle("A{$totalRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$totalRow}")->getFont()->setBold(true);

        // Formula SUM untuk kolom nominal
        $numericCols = ['I', 'J', 'L', 'N', 'P', 'R', 'T', 'U'];
        foreach ($numericCols as $col) {
            $sheet->setCellValue("{$col}{$totalRow}", "=SUM({$col}6:{$col}{$lastDataRow})");
            $sheet->getStyle("{$col}{$totalRow}")->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"_);_(@_)');
            $sheet->getStyle("{$col}{$totalRow}")->getFont()->setBold(true);
            $sheet->getStyle("{$col}{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // Background dan Border Baris Total
        $sheet->getStyle("A{$totalRow}:U{$totalRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF1F5F9'); // Muted / light gray

        $sheet->getStyle("A{$totalRow}:U{$totalRow}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A{$totalRow}:U{$totalRow}")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_DOUBLE);

        // Border tipis untuk seluruh tabel data
        $sheet->getStyle("A5:U{$lastDataRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB('FFE2E8F0');

        $sheet->getStyle("A{$totalRow}:U{$totalRow}")->getBorders()->getOutline()
            ->setBorderStyle(Border::BORDER_MEDIUM)
            ->getColor()->setARGB('FF94A3B8');
    }

    /**
     * Konfigurasi Kop Laporan, Header Kolom, Lebar Kolom, dan Freeze Pane.
     */
    protected function setupHeaderAndColumns($sheet, string $monthName): void
    {
        // 1. Judul / Kop Laporan
        $sheet->mergeCells('A1:U1');
        $sheet->setCellValue('A1', 'PEMERINTAH KABUPATEN MINAHASA UTARA - RSUD MARIA WALANDA MARAMIS');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FF1E293B');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:U2');
        $sheet->setCellValue('A2', 'REGISTER PENCAIRAN SURAT PERINTAH PENCAIRAN DANA (SPD)');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FF1E293B');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A3:U3');
        $sheet->setCellValue('A3', 'PERIODE: ' . mb_strtoupper($monthName));
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF64748B');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 2. Daftar 21 Kolom Header
        $headers = [
            'A' => 'No.',
            'B' => 'BULAN',
            'C' => 'TGGL SPP DAN SPM',
            'D' => 'No. SPM',
            'E' => 'PIHAK KE-3',
            'F' => 'URAIAN KEPERLUAN',
            'G' => 'KODE REK',
            'H' => 'URAIAN REK',
            'I' => 'JUMLAH YANG DIMINTA',
            'J' => 'PPN',
            'K' => 'KODE BILING',
            'L' => 'PPH 21',
            'M' => 'KODE BILING2',
            'N' => 'PPH 22',
            'O' => 'KODE BILING3',
            'P' => 'PPH 23',
            'Q' => 'KODE BILING4',
            'R' => 'PPH FINAL',
            'S' => 'KODE BILING5',
            'T' => 'JUMLAH POTONGAN',
            'U' => 'JUMLAH DITERIMA',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}5", $title);
        }

        // Styling Baris Header (Baris 5) - Deep Navy Style Guide
        $headerRange = 'A5:U5';
        $sheet->getStyle($headerRange)->getFont()
            ->setBold(true)
            ->setSize(9)
            ->getColor()->setARGB('FFFFFFFF');

        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF1E293B'); // Deep Navy

        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        $sheet->getRowDimension(5)->setRowHeight(32);

        // 3. Lebar Kolom yang Dioptimalkan
        $columnWidths = [
            'A' => 6,   // No.
            'B' => 14,  // BULAN
            'C' => 16,  // TGGL SPP DAN SPM
            'D' => 24,  // No. SPM
            'E' => 28,  // PIHAK KE-3
            'F' => 40,  // URAIAN KEPERLUAN
            'G' => 24,  // KODE REK
            'H' => 36,  // URAIAN REK
            'I' => 20,  // JUMLAH YANG DIMINTA
            'J' => 15,  // PPN
            'K' => 18,  // KODE BILING
            'L' => 15,  // PPH 21
            'M' => 18,  // KODE BILING2
            'N' => 15,  // PPH 22
            'O' => 18,  // KODE BILING3
            'P' => 15,  // PPH 23
            'Q' => 18,  // KODE BILING4
            'R' => 15,  // PPH FINAL
            'S' => 18,  // KODE BILING5
            'T' => 19,  // JUMLAH POTONGAN
            'U' => 20,  // JUMLAH DITERIMA
        ];

        foreach ($columnWidths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // 4. Freeze Panes di bawah baris header
        $sheet->freezePane('A6');
    }

    /**
     * Menentukan nama penerima / pihak ketiga.
     */
    protected function resolveRecipientName(Expenditure $item): string
    {
        if ($item->payment_method === 'rekanan' && $item->vendor) {
            return $item->vendor->name;
        }
        if ($item->payment_method === 'pegawai') {
            return 'Pegawai Internal';
        }
        if ($item->payment_method === 'ls_bendahara') {
            return $item->treasurer ? 'Bendahara: ' . $item->treasurer->name : 'Kas Bendahara';
        }

        return $item->vendor?->name ?? $item->payment_method ?? '-';
    }
}
