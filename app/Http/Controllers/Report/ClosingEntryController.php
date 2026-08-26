<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\AccountCode;
use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ClosingEntryController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Reports/ClosingEntry/Index');
    }

    public function data(Request $request)
    {
        $year = $request->input('year', date('Y'));
        
        $closingJournal = Journal::where('type', 'closing')
            ->whereYear('date', $year)
            ->first();

        // Cari semua akun nominal LRA (4, 5, 6) dan LO (7, 8, 9)
        $nominalAccounts = AccountCode::where(function($query) {
                $query->where('code', 'like', '4.%')
                      ->orWhere('code', 'like', '5.%')
                      ->orWhere('code', 'like', '6.%')
                      ->orWhere('code', 'like', '7.%')
                      ->orWhere('code', 'like', '8.%')
                      ->orWhere('code', 'like', '9.%');
            })
            ->get();

        $accountIds = $nominalAccounts->pluck('id');
        
        $mutations = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->whereIn('journal_details.account_code_id', $accountIds)
            ->whereYear('journals.date', $year)
            ->where('journals.status', 'posted')
            ->where('journals.type', '!=', 'closing') // Abaikan jurnal tutup buku itu sendiri
            ->select(
                'journal_details.account_code_id',
                DB::raw('SUM(journal_details.debit) as total_debit'),
                DB::raw('SUM(journal_details.credit) as total_credit')
            )
            ->groupBy('journal_details.account_code_id')
            ->get()
            ->keyBy('account_code_id');

        $previewData = [];
        $totalDebitToClose = 0;
        $totalCreditToClose = 0;
        
        foreach ($nominalAccounts as $account) {
            $mutation = $mutations->get($account->id);
            if (!$mutation) continue;

            $debit = (float) $mutation->total_debit;
            $credit = (float) $mutation->total_credit;

            if ($debit == 0 && $credit == 0) continue;

            $normalBalance = $account->getNormalBalance();
            $balance = 0;
            $closingDebit = 0;
            $closingCredit = 0;

            if ($normalBalance === 'D') {
                $balance = $debit - $credit;
                if ($balance > 0) {
                    $closingCredit = $balance; 
                } elseif ($balance < 0) {
                    $closingDebit = abs($balance);
                }
            } else {
                $balance = $credit - $debit;
                if ($balance > 0) {
                    $closingDebit = $balance; 
                } elseif ($balance < 0) {
                    $closingCredit = abs($balance);
                }
            }

            if ($closingDebit > 0 || $closingCredit > 0) {
                $type = in_array(substr($account->code, 0, 1), ['4', '5', '6']) ? 'LRA' : 'LO';
                $previewData[] = [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $type,
                    'normal_balance' => $normalBalance,
                    'closing_debit' => $closingDebit,
                    'closing_credit' => $closingCredit,
                ];
                $totalDebitToClose += $closingDebit;
                $totalCreditToClose += $closingCredit;
            }
        }

        // Pisahkan LRA dan LO
        $lraDebit = collect($previewData)->where('type', 'LRA')->sum('closing_debit');
        $lraCredit = collect($previewData)->where('type', 'LRA')->sum('closing_credit');
        $loDebit = collect($previewData)->where('type', 'LO')->sum('closing_debit');
        $loCredit = collect($previewData)->where('type', 'LO')->sum('closing_credit');

        // LRA ditutup ke SAL (3.1.03)
        $salAccount = AccountCode::where('code', '3.1.03')->first();
        $lraDifference = $lraDebit - $lraCredit;
        
        // LO ditutup ke Surplus/Defisit (3.1.02)
        $surplusDefisitAccount = AccountCode::where('code', '3.1.02')->first();
        if (!$surplusDefisitAccount) {
            $surplusDefisitAccount = AccountCode::where('code', '3.1.01')->first();
        }
        $loDifference = $loDebit - $loCredit;

        return response()->json([
            'data' => $previewData,
            'status' => $closingJournal ? 'closed' : 'open',
            'closing_date' => $closingJournal ? $closingJournal->date : null,
            'equity_accounts' => [
                'sal' => $salAccount ? [
                    'id' => $salAccount->id,
                    'code' => $salAccount->code,
                    'name' => $salAccount->name,
                    'amount' => abs($lraDifference),
                    'position' => $lraDifference > 0 ? 'credit' : 'debit' 
                ] : null,
                'surplus_defisit' => $surplusDefisitAccount ? [
                    'id' => $surplusDefisitAccount->id,
                    'code' => $surplusDefisitAccount->code,
                    'name' => $surplusDefisitAccount->name,
                    'amount' => abs($loDifference),
                    'position' => $loDifference > 0 ? 'credit' : 'debit'
                ] : null
            ],
            'summary' => [
                'total_closing_debit' => $totalDebitToClose,
                'total_closing_credit' => $totalCreditToClose,
                'difference' => $lraDifference + $loDifference
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
        ]);

        $year = $request->input('year');
        
        // Panggil endpoint data secara internal untuk mendapatkan perhitungan final
        $request->merge(['year' => $year]);
        $dataResponse = $this->data($request)->getData(true);

        if ($dataResponse['status'] === 'closed') {
            return response()->json(['message' => 'Tutup buku untuk tahun ini sudah dilakukan sebelumnya.'], 400);
        }

        if (empty($dataResponse['data'])) {
            return response()->json(['message' => 'Tidak ada saldo akun nominal yang perlu ditutup.'], 400);
        }

        if (!$dataResponse['equity_accounts']['sal'] || !$dataResponse['equity_accounts']['surplus_defisit']) {
            return response()->json(['message' => 'Akun penampung (SAL 3.1.03 atau Surplus/Defisit 3.1.02) tidak ditemukan di master data.'], 400);
        }

        DB::beginTransaction();
        try {
            $referenceNo = 'CL-' . $year;

            $journal = Journal::create([
                'date' => Carbon::create($year, 12, 31)->toDateString(),
                'reference_no' => $referenceNo,
                'description' => 'Jurnal Penutup Tahun Anggaran ' . $year,
                'type' => 'closing',
                'status' => 'posted', 
                'created_by' => Auth::id()
            ]);

            $insertData = [];
            
            // Masukkan semua rincian penutupan akun nominal
            foreach ($dataResponse['data'] as $item) {
                $insertData[] = [
                    'journal_id' => $journal->id,
                    'account_code_id' => $item['id'],
                    'debit' => $item['closing_debit'],
                    'credit' => $item['closing_credit'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Masukkan rincian penyeimbang ke SAL (Untuk LRA)
            $sal = $dataResponse['equity_accounts']['sal'];
            if ($sal && $sal['amount'] > 0) {
                $insertData[] = [
                    'journal_id' => $journal->id,
                    'account_code_id' => $sal['id'],
                    'debit' => $sal['position'] === 'debit' ? $sal['amount'] : 0,
                    'credit' => $sal['position'] === 'credit' ? $sal['amount'] : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Masukkan rincian penyeimbang ke Surplus/Defisit (Untuk LO)
            $sd = $dataResponse['equity_accounts']['surplus_defisit'];
            if ($sd && $sd['amount'] > 0) {
                $insertData[] = [
                    'journal_id' => $journal->id,
                    'account_code_id' => $sd['id'],
                    'debit' => $sd['position'] === 'debit' ? $sd['amount'] : 0,
                    'credit' => $sd['position'] === 'credit' ? $sd['amount'] : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            JournalDetail::insert($insertData);

            DB::commit();
            return response()->json(['message' => 'Tutup Buku berhasil dieksekusi. Saldo nominal telah di-nol-kan.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal mengeksekusi tutup buku: ' . $e->getMessage()], 500);
        }
    }
}
