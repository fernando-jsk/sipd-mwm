<?php

namespace App\Services;

use App\Models\AccountCode;
use Illuminate\Support\Facades\DB;

class LpeService
{
    /**
     * Get aggregated Laporan Perubahan Ekuitas (LPE) Data up to a specific year.
     *
     * @param int $year
     * @return array
     */
    public function getLpeData($year)
    {
        $year = (int) $year;
        
        // 1. Ekuitas Awal
        // Saldo akhir Ekuitas per 31 Desember tahun sebelumnya, ditambah opening_balance tahun berjalan
        
        // Ekuitas dari jurnal tahun-tahun sebelumnya
        $ekuitasAwalPrevYear = DB::table('journal_details')
            ->join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('account_codes', 'journal_details.account_code_id', '=', 'account_codes.id')
            ->where('journals.status', 'posted')
            ->whereYear('journals.date', '<', $year)
            ->where(function($q) {
                $q->where('account_codes.code', 'like', '3%')
                  ->orWhere('account_codes.code', 'like', '4%')
                  ->orWhere('account_codes.code', 'like', '5%')
                  ->orWhere('account_codes.code', 'like', '6%');
            })
            ->selectRaw("
                SUM(CASE WHEN account_codes.code LIKE '3%' THEN credit - debit ELSE 0 END) +
                SUM(CASE WHEN account_codes.code LIKE '4%' THEN credit - debit ELSE 0 END) +
                SUM(CASE WHEN account_codes.code LIKE '5%' THEN debit - credit ELSE 0 END) +
                SUM(CASE WHEN account_codes.code LIKE '6%' THEN credit - debit ELSE 0 END)
            AS total")
            ->value('total') ?? 0;

        // Opening balance ekuitas di tahun berjalan
        $openingBalanceCurrentYear = DB::table('journal_details')
            ->join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('account_codes', 'journal_details.account_code_id', '=', 'account_codes.id')
            ->where('journals.status', 'posted')
            ->whereYear('journals.date', $year)
            ->where('journals.type', 'opening_balance')
            ->where('account_codes.code', 'like', '3%')
            ->selectRaw('SUM(credit - debit) as total')
            ->value('total') ?? 0;

        $ekuitasAwal = (float)($ekuitasAwalPrevYear + $openingBalanceCurrentYear);

        // 2. Surplus/Defisit-LO Tahun Berjalan
        // Hanya dari jurnal operasional (general/adjustment) di tahun berjalan
        $nominalMutations = DB::table('journal_details')
            ->join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('account_codes', 'journal_details.account_code_id', '=', 'account_codes.id')
            ->where('journals.status', 'posted')
            ->whereYear('journals.date', $year)
            ->where('journals.type', '!=', 'opening_balance')
            ->where(function($q) {
                $q->where('account_codes.code', 'like', '4%')
                  ->orWhere('account_codes.code', 'like', '5%')
                  ->orWhere('account_codes.code', 'like', '6%');
            })
            ->selectRaw("
                SUM(CASE WHEN account_codes.code LIKE '4%' THEN credit - debit ELSE 0 END) as total_pendapatan,
                SUM(CASE WHEN account_codes.code LIKE '5%' THEN debit - credit ELSE 0 END) as total_belanja,
                SUM(CASE WHEN account_codes.code LIKE '6%' THEN credit - debit ELSE 0 END) as total_pembiayaan
            ")
            ->first();

        $surplusDefisit = (float)(($nominalMutations->total_pendapatan ?? 0) - ($nominalMutations->total_belanja ?? 0) + ($nominalMutations->total_pembiayaan ?? 0));

        // 3. Koreksi & Dampak Kumulatif Ekuitas
        // Mutasi yang langsung menyasar akun Ekuitas (3) di tahun berjalan melalui jurnal operasional
        $koreksi = DB::table('journal_details')
            ->join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->join('account_codes', 'journal_details.account_code_id', '=', 'account_codes.id')
            ->where('journals.status', 'posted')
            ->whereYear('journals.date', $year)
            ->where('journals.type', '!=', 'opening_balance')
            ->where('account_codes.code', 'like', '3%')
            ->selectRaw('SUM(credit - debit) as total')
            ->value('total') ?? 0;

        $koreksi = (float)$koreksi;

        // 4. Ekuitas Akhir
        $ekuitasAkhir = $ekuitasAwal + $surplusDefisit + $koreksi;

        return [
            'ekuitas_awal' => $ekuitasAwal,
            'surplus_defisit' => $surplusDefisit,
            'koreksi' => $koreksi,
            'ekuitas_akhir' => $ekuitasAkhir
        ];
    }
}
