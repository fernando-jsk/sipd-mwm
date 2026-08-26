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

class OpeningBalanceController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Reports/OpeningBalance/Index');
    }

    public function data(Request $request)
    {
        $year = $request->input('year', date('Y'));
        
        $journal = Journal::where('type', 'opening_balance')
            ->whereYear('date', $year)
            ->first();
            
        $details = $journal ? $journal->details->keyBy('account_code_id') : collect();

        // Hanya mengambil akun Riil (Aset, Kewajiban, Ekuitas)
        $accounts = AccountCode::where('is_active', true)
            ->where(function($query) {
                $query->where('code', 'like', '1.%')
                      ->orWhere('code', 'like', '2.%')
                      ->orWhere('code', 'like', '3.%');
            })
            ->orderBy('code')
            ->get()
            ->map(function ($account) use ($details) {
                $detail = $details->get($account->id);
                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'debit' => $detail ? $detail->debit : 0,
                    'credit' => $detail ? $detail->credit : 0,
                ];
            });

        return response()->json([
            'data' => $accounts,
            'journal_status' => $journal ? $journal->status : 'draft'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'balances' => 'required|array',
            'balances.*.id' => 'required|exists:account_codes,id',
            'balances.*.debit' => 'numeric|min:0',
            'balances.*.credit' => 'numeric|min:0',
        ]);

        $year = $request->input('year');
        $balances = $request->input('balances');
        
        // Validasi Balance
        $totalDebit = collect($balances)->sum('debit');
        $totalCredit = collect($balances)->sum('credit');
        
        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return response()->json([
                'message' => 'Total Debit dan Kredit tidak seimbang! (Unbalanced)'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $journal = Journal::where('type', 'opening_balance')
                ->whereYear('date', $year)
                ->first();

            $referenceNo = 'SA-' . $year;

            if (!$journal) {
                $journal = Journal::create([
                    'date' => Carbon::create($year, 1, 1)->toDateString(),
                    'reference_no' => $referenceNo,
                    'description' => 'Saldo Awal Tahun Anggaran ' . $year,
                    'type' => 'opening_balance',
                    'status' => 'posted', 
                    'created_by' => Auth::id()
                ]);
            } else {
                $journal->details()->delete();
                $journal->update([
                    'status' => 'posted',
                    'updated_at' => now(),
                ]);
            }

            $insertData = [];
            foreach ($balances as $balance) {
                if ($balance['debit'] > 0 || $balance['credit'] > 0) {
                    $insertData[] = [
                        'journal_id' => $journal->id,
                        'account_code_id' => $balance['id'],
                        'debit' => $balance['debit'],
                        'credit' => $balance['credit'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (count($insertData) > 0) {
                JournalDetail::insert($insertData);
            }

            DB::commit();
            return response()->json(['message' => 'Saldo Awal berhasil disimpan.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan saldo awal: ' . $e->getMessage()], 500);
        }
    }
}
