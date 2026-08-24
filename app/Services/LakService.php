<?php

namespace App\Services;

use App\Models\AccountCode;
use App\Models\ReceiptDetail;
use App\Models\ExpenditureDetail;
use Illuminate\Support\Facades\DB;

class LakService
{
    /**
     * Get aggregated LAK Data.
     *
     * @param string $year
     * @param string|null $period
     * @return array
     */
    public function getLakData($year, $period = null)
    {
        // 1. Fetch all active account codes
        $accounts = AccountCode::where('is_active', true)
            ->orderBy('code')
            ->get()
            ->keyBy('id')
            ->toArray();

        $applyPeriodFilter = function ($query, $dateColumn, $period) {
            if (!$period || $period === 'all') return;
            switch ($period) {
                case 'q1': $query->whereMonth($dateColumn, '>=', 1)->whereMonth($dateColumn, '<=', 3); break;
                case 'q2': $query->whereMonth($dateColumn, '>=', 4)->whereMonth($dateColumn, '<=', 6); break;
                case 'q3': $query->whereMonth($dateColumn, '>=', 7)->whereMonth($dateColumn, '<=', 9); break;
                case 'q4': $query->whereMonth($dateColumn, '>=', 10)->whereMonth($dateColumn, '<=', 12); break;
                case 's1': $query->whereMonth($dateColumn, '>=', 1)->whereMonth($dateColumn, '<=', 6); break;
                case 's2': $query->whereMonth($dateColumn, '>=', 7)->whereMonth($dateColumn, '<=', 12); break;
                default: $query->whereMonth($dateColumn, $period); break;
            }
        };

        // 2. Fetch Cash Inflows (Receipts)
        $receiptQuery = ReceiptDetail::join('receipts', 'receipt_details.receipt_id', '=', 'receipts.id')
            ->whereYear('receipts.date', $year)
            ->where('receipts.status', '!=', 'rejected');
            
        $applyPeriodFilter($receiptQuery, 'receipts.date', $period);

        $inflows = $receiptQuery->select('receipt_details.account_code_id', DB::raw('SUM(receipt_details.amount) as total'))
            ->groupBy('receipt_details.account_code_id')
            ->pluck('total', 'receipt_details.account_code_id')
            ->toArray();

        // 3. Fetch Cash Outflows (Expenditures)
        $expenditureQuery = ExpenditureDetail::join('expenditures', 'expenditure_details.expenditure_id', '=', 'expenditures.id')
            ->whereYear('expenditures.date', $year)
            ->where('expenditures.status', '!=', 'rejected');

        $applyPeriodFilter($expenditureQuery, 'expenditures.date', $period);

        $outflows = $expenditureQuery->select('expenditure_details.account_code_id', DB::raw('SUM(expenditure_details.amount) as total'))
            ->groupBy('expenditure_details.account_code_id')
            ->pluck('total', 'expenditure_details.account_code_id')
            ->toArray();

        // 4. Group data into LAK Categories
        // Categories:
        // A. Aktivitas Operasi
        // B. Aktivitas Investasi
        // C. Aktivitas Pendanaan
        
        $lak = [
            'operasi' => ['inflows' => [], 'outflows' => [], 'net' => 0, 'total_in' => 0, 'total_out' => 0],
            'investasi' => ['inflows' => [], 'outflows' => [], 'net' => 0, 'total_in' => 0, 'total_out' => 0],
            'pendanaan' => ['inflows' => [], 'outflows' => [], 'net' => 0, 'total_in' => 0, 'total_out' => 0],
        ];

        // Process Inflows
        foreach ($inflows as $accountId => $amount) {
            if (!isset($accounts[$accountId])) continue;
            $account = $accounts[$accountId];
            $code = $account['code'];
            
            $item = ['id' => $account['id'], 'code' => $code, 'name' => $account['name'], 'amount' => (float)$amount];
            
            if (str_starts_with($code, '4')) {
                // Pendapatan -> Operasi Inflow
                $lak['operasi']['inflows'][] = $item;
                $lak['operasi']['total_in'] += $item['amount'];
            } elseif (str_starts_with($code, '6.1')) {
                // Penerimaan Pembiayaan -> Pendanaan Inflow
                $lak['pendanaan']['inflows'][] = $item;
                $lak['pendanaan']['total_in'] += $item['amount'];
            }
        }

        // Process Outflows
        foreach ($outflows as $accountId => $amount) {
            if (!isset($accounts[$accountId])) continue;
            $account = $accounts[$accountId];
            $code = $account['code'];
            
            $item = ['id' => $account['id'], 'code' => $code, 'name' => $account['name'], 'amount' => (float)$amount];
            
            if (str_starts_with($code, '5.1') || str_starts_with($code, '5.3') || str_starts_with($code, '5.4')) {
                // Belanja Operasi -> Operasi Outflow
                $lak['operasi']['outflows'][] = $item;
                $lak['operasi']['total_out'] += $item['amount'];
            } elseif (str_starts_with($code, '5.2')) {
                // Belanja Modal -> Investasi Outflow
                $lak['investasi']['outflows'][] = $item;
                $lak['investasi']['total_out'] += $item['amount'];
            } elseif (str_starts_with($code, '6.2')) {
                // Pengeluaran Pembiayaan -> Pendanaan Outflow
                $lak['pendanaan']['outflows'][] = $item;
                $lak['pendanaan']['total_out'] += $item['amount'];
            }
        }

        // Calculate Nets
        $lak['operasi']['net'] = $lak['operasi']['total_in'] - $lak['operasi']['total_out'];
        $lak['investasi']['net'] = $lak['investasi']['total_in'] - $lak['investasi']['total_out'];
        $lak['pendanaan']['net'] = $lak['pendanaan']['total_in'] - $lak['pendanaan']['total_out'];

        $totalNetCashFlow = $lak['operasi']['net'] + $lak['investasi']['net'] + $lak['pendanaan']['net'];
        $totalInflow = $lak['operasi']['total_in'] + $lak['investasi']['total_in'] + $lak['pendanaan']['total_in'];
        $totalOutflow = $lak['operasi']['total_out'] + $lak['investasi']['total_out'] + $lak['pendanaan']['total_out'];

        // Sort items by code
        $sortFn = function($a, $b) { return strcmp($a['code'], $b['code']); };
        usort($lak['operasi']['inflows'], $sortFn);
        usort($lak['operasi']['outflows'], $sortFn);
        usort($lak['investasi']['inflows'], $sortFn);
        usort($lak['investasi']['outflows'], $sortFn);
        usort($lak['pendanaan']['inflows'], $sortFn);
        usort($lak['pendanaan']['outflows'], $sortFn);

        // Transform into a Tree format suitable for the table
        $tree = [
            [
                'id' => 'OP',
                'name' => 'ARUS KAS DARI AKTIVITAS OPERASI',
                'is_header' => true,
                'amount' => $lak['operasi']['net'],
                'children' => [
                    [
                        'id' => 'OP_IN',
                        'name' => 'Arus Kas Masuk',
                        'is_subheader' => true,
                        'amount' => $lak['operasi']['total_in'],
                        'children' => $lak['operasi']['inflows']
                    ],
                    [
                        'id' => 'OP_OUT',
                        'name' => 'Arus Kas Keluar',
                        'is_subheader' => true,
                        'amount' => $lak['operasi']['total_out'],
                        'children' => $lak['operasi']['outflows']
                    ]
                ]
            ],
            [
                'id' => 'INV',
                'name' => 'ARUS KAS DARI AKTIVITAS INVESTASI',
                'is_header' => true,
                'amount' => $lak['investasi']['net'],
                'children' => [
                    [
                        'id' => 'INV_IN',
                        'name' => 'Arus Kas Masuk',
                        'is_subheader' => true,
                        'amount' => $lak['investasi']['total_in'],
                        'children' => $lak['investasi']['inflows']
                    ],
                    [
                        'id' => 'INV_OUT',
                        'name' => 'Arus Kas Keluar',
                        'is_subheader' => true,
                        'amount' => $lak['investasi']['total_out'],
                        'children' => $lak['investasi']['outflows']
                    ]
                ]
            ],
            [
                'id' => 'FIN',
                'name' => 'ARUS KAS DARI AKTIVITAS PENDANAAN',
                'is_header' => true,
                'amount' => $lak['pendanaan']['net'],
                'children' => [
                    [
                        'id' => 'FIN_IN',
                        'name' => 'Arus Kas Masuk',
                        'is_subheader' => true,
                        'amount' => $lak['pendanaan']['total_in'],
                        'children' => $lak['pendanaan']['inflows']
                    ],
                    [
                        'id' => 'FIN_OUT',
                        'name' => 'Arus Kas Keluar',
                        'is_subheader' => true,
                        'amount' => $lak['pendanaan']['total_out'],
                        'children' => $lak['pendanaan']['outflows']
                    ]
                ]
            ]
        ];

        return [
            'tree' => $tree,
            'summary' => [
                'total_inflow' => $totalInflow,
                'total_outflow' => $totalOutflow,
                'net_cash_flow' => $totalNetCashFlow
            ]
        ];
    }
}
