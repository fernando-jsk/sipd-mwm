<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\ReceiptType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class ReceiptImportService
{
    /**
     * Parse and import CSV data into receipts.
     *
     * @param string $path Path to the CSV file
     * @param string $status Target status (draft/submitted)
     * @return int Number of receipts created
     * @throws Exception
     */
    public function import(string $path, string $status): int
    {
        $data = array_map(function ($v) {
            return str_getcsv($v, ';');
        }, file($path));

        if (count($data) < 8) {
            throw new Exception('Format file CSV tidak valid. Harus dipisahkan dengan titik koma (;).');
        }

        $mainHeaders = $data[4];
        $subHeaders = $data[6];

        $receiptTypes = ReceiptType::all();
        $createdReceiptsCount = 0;

        DB::beginTransaction();
        try {
            // 1. Pemetaan Header Dinamis dan Auto-Creation Kategori
            $columnMap = [];
            $currentMainHeader = '';
            
            $maxCols = max(count($mainHeaders), count($subHeaders));
            
            for ($col = 2; $col < $maxCols; $col++) {
                $mainVal = isset($mainHeaders[$col]) ? trim($mainHeaders[$col]) : '';
                if (!empty($mainVal)) {
                    $currentMainHeader = $mainVal;
                }
                
                $subVal = isset($subHeaders[$col]) ? trim($subHeaders[$col]) : '';
                
                if (empty($currentMainHeader)) {
                    continue; 
                }
                
                // Abaikan jika ada kata "total" (case-insensitive)
                if (stripos($currentMainHeader, 'total') !== false || stripos($subVal, 'total') !== false) {
                    continue;
                }
                
                // Cari atau buat Parent Kategori
                $parentType = $receiptTypes->where('name', $currentMainHeader)->whereNull('parent_id')->first();
                if (!$parentType) {
                    // Coba case-insensitive search
                    $parentType = $receiptTypes->where('parent_id', null)
                        ->filter(fn($t) => strtolower($t->name) === strtolower($currentMainHeader))
                        ->first();
                        
                    if (!$parentType) {
                        $parentType = ReceiptType::create([
                            'name' => $currentMainHeader,
                            'is_active' => true,
                            'created_by' => Auth::id() ?? 1,
                        ]);
                        $receiptTypes->push($parentType); // Tambah ke cache lokal
                    }
                }
                
                // Cari atau buat Sub Kategori
                $subType = null;
                if (!empty($subVal)) {
                    $subType = $receiptTypes->where('parent_id', $parentType->id)->where('name', $subVal)->first();
                    if (!$subType) {
                        $subType = $receiptTypes->where('parent_id', $parentType->id)
                            ->filter(fn($t) => strtolower($t->name) === strtolower($subVal))
                            ->first();
                            
                        if (!$subType) {
                            $subType = ReceiptType::create([
                                'name' => $subVal,
                                'parent_id' => $parentType->id,
                                'is_active' => true,
                                'created_by' => Auth::id() ?? 1,
                            ]);
                            $receiptTypes->push($subType); // Tambah ke cache lokal
                        }
                    }
                }
                
                $columnMap[$col] = [
                    'parent_id' => $parentType->id,
                    'sub_id' => $subType ? $subType->id : null,
                    'payer_name' => !empty($subVal) ? $subVal : $currentMainHeader,
                ];
            }

            // 2. Membaca Data Transaksi
            for ($i = 7; $i < count($data); $i++) {
                $row = $data[$i];
                if (!isset($row[1]) || empty(trim($row[1])) || stripos($row[1], 'total') !== false || stripos($row[1], 'jasa') !== false) {
                    continue;
                }

                $tanggalString = trim($row[1]);
                $dateObj = \DateTime::createFromFormat('d/m/Y', $tanggalString);
                if (!$dateObj) {
                    continue;
                }
                $formattedDate = $dateObj->format('Y-m-d');

                $groupedReceipts = [];

                for ($col = 2; $col < count($row); $col++) {
                    if (!isset($columnMap[$col])) {
                        continue;
                    }

                    $map = $columnMap[$col];
                    $parentTypeId = $map['parent_id'];
                    $subTypeId = $map['sub_id'];
                    $payerName = $map['payer_name'];

                    $amountString = trim($row[$col]);
                    if (empty($amountString) || $amountString === '-' || $amountString === 'Rp-') {
                        continue;
                    }

                    $amountString = preg_replace('/[^0-9]/', '', explode(',', $amountString)[0]);
                    $amount = (float)$amountString;

                    if ($amount > 0) {
                        $groupKey = $parentTypeId . '_' . $subTypeId . '_' . md5($payerName);
                        if (!isset($groupedReceipts[$groupKey])) {
                            $groupedReceipts[$groupKey] = [
                                'parent_id' => $parentTypeId,
                                'sub_id' => $subTypeId,
                                'payer_name' => $payerName ?: 'Hamba Allah',
                                'details' => []
                            ];
                        }

                        $groupedReceipts[$groupKey]['details'][] = [
                            'amount' => $amount
                        ];
                    }
                }

                foreach ($groupedReceipts as $key => $group) {
                    $receipt = Receipt::create([
                        'document_number' => null,
                        'date' => $formattedDate,
                        'receipt_type_id' => $group['parent_id'],
                        'receipt_sub_type_id' => $group['sub_id'],
                        'description' => $group['payer_name'],
                        'payer_name' => $group['payer_name'],
                        'payment_method' => 'tunai',
                        'status' => $status,
                        'treasurer_id' => Auth::id(),
                        'created_by' => Auth::id(),
                    ]);

                    foreach ($group['details'] as $detail) {
                        $receipt->details()->create([
                            'account_code_id' => null,
                            'amount' => $detail['amount']
                        ]);
                    }

                    $createdReceiptsCount++;
                }
            }

            activity('penerimaan')->log("Melakukan import {$createdReceiptsCount} penerimaan dari file CSV.");
            DB::commit();

            return $createdReceiptsCount;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
