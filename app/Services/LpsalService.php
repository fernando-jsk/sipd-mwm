<?php

namespace App\Services;

use App\Models\AccountCode;
use App\Models\ReceiptDetail;
use Illuminate\Support\Facades\DB;

class LpsalService
{
    protected $lraService;

    public function __construct(LraService $lraService)
    {
        $this->lraService = $lraService;
    }

    /**
     * Get aggregated LPSAL (Laporan Perubahan Saldo Anggaran Lebih) data.
     *
     * @param int|string $year
     * @return array
     */
    public function getLpsalData($year)
    {
        $year = (int) $year;

        // 1. Saldo Anggaran Lebih Awal (SAL Awal)
        // Ambil dari mutasi akun Ekuitas SAL (3.1.03) sebelum tahun berjalan + opening_balance tahun berjalan
        $salAwalPrevYear = DB::table('journal_details')
            ->join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('account_codes', 'journal_details.account_code_id', '=', 'account_codes.id')
            ->where('journals.status', 'posted')
            ->whereYear('journals.date', '<', $year)
            ->where('account_codes.code', 'like', '3.1.03%')
            ->selectRaw('SUM(credit - debit) as total')
            ->value('total') ?? 0;

        $openingBalanceCurrentYear = DB::table('journal_details')
            ->join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('account_codes', 'journal_details.account_code_id', '=', 'account_codes.id')
            ->where('journals.status', 'posted')
            ->whereYear('journals.date', $year)
            ->where('journals.type', 'opening_balance')
            ->where('account_codes.code', 'like', '3.1.03%')
            ->selectRaw('SUM(credit - debit) as total')
            ->value('total') ?? 0;

        $salAwal = (float)($salAwalPrevYear + $openingBalanceCurrentYear);

        // 2. Penggunaan SAL sebagai Penerimaan Pembiayaan Tahun Berjalan (Akun 6.1.01)
        // Dihitung dari realisasi penerimaan pembiayaan pada akun SiLPA tahun sebelumnya
        $penggunaanSal = DB::table('journal_details')
            ->join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('account_codes', 'journal_details.account_code_id', '=', 'account_codes.id')
            ->where('journals.status', 'posted')
            ->whereYear('journals.date', $year)
            ->where('journals.type', '!=', 'opening_balance')
            ->where('account_codes.code', 'like', '6.1.01%')
            ->selectRaw('SUM(credit - debit) as total')
            ->value('total') ?? 0;

        // Fallback cek dari ReceiptDetail jika belum ada jurnal yang diposting
        if ($penggunaanSal == 0) {
            $penggunaanSalReceipt = ReceiptDetail::join('receipts', 'receipt_details.receipt_id', '=', 'receipts.id')
                ->join('account_codes', 'receipt_details.account_code_id', '=', 'account_codes.id')
                ->whereYear('receipts.date', $year)
                ->where('receipts.status', '!=', 'rejected')
                ->where('account_codes.code', 'like', '6.1.01%')
                ->sum('receipt_details.amount') ?? 0;

            if ($penggunaanSalReceipt > 0) {
                $penggunaanSal = $penggunaanSalReceipt;
            }
        }

        $penggunaanSal = (float) $penggunaanSal;

        // 3. Subtotal 1 (SAL Awal - Penggunaan SAL)
        $subtotal1 = $salAwal - $penggunaanSal;

        // 4. Sisa Lebih/Kurang Pembiayaan Anggaran (SiLPA/SiKPA) Tahun Berjalan dari LRA
        $lraData = $this->lraService->getLraData($year);
        $silpa = (float) ($lraData['summary']['silpa'] ?? 0);

        // 5. Subtotal 2 (Subtotal 1 + SiLPA)
        $subtotal2 = $subtotal1 + $silpa;

        // 6. Koreksi Kesalahan Pembukuan Tahun Sebelumnya / Lain-lain (Mutasi penyesuaian langsung ke akun 3.1.03)
        $koreksi = DB::table('journal_details')
            ->join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('account_codes', 'journal_details.account_code_id', '=', 'account_codes.id')
            ->where('journals.status', 'posted')
            ->whereYear('journals.date', $year)
            ->where('journals.type', '!=', 'opening_balance')
            ->where('account_codes.code', 'like', '3.1.03%')
            ->selectRaw('SUM(credit - debit) as total')
            ->value('total') ?? 0;

        $koreksi = (float) $koreksi;

        // 7. Saldo Anggaran Lebih Akhir (SAL Akhir)
        $salAkhir = $subtotal2 + $koreksi;

        return [
            'sal_awal' => $salAwal,
            'penggunaan_sal' => $penggunaanSal,
            'subtotal_1' => $subtotal1,
            'silpa' => $silpa,
            'subtotal_2' => $subtotal2,
            'koreksi' => $koreksi,
            'sal_akhir' => $salAkhir,
        ];
    }
}
