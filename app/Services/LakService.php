<?php

namespace App\Services;

use App\Models\AccountCode;
use App\Services\BudgetRealizationService;

class LakService
{
    protected BudgetRealizationService $budgetRealizationService;

    public function __construct(BudgetRealizationService $budgetRealizationService)
    {
        $this->budgetRealizationService = $budgetRealizationService;
    }
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

        // 2. Fetch Cash Inflows (Receipts) from SSOT
        $inflows = $this->budgetRealizationService->getRevenueRealizationByAccount($year, $period);

        // 3. Fetch Cash Outflows (Expenditures) from SSOT
        $outflows = $this->budgetRealizationService->getExpenditureRealizationByAccount($year, $period);

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
