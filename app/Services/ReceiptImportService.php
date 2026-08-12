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
                    $parentName = '';
                    $subName = '';
                    $payerName = '';

                    if ($col >= 2 && $col <= 20) {
                        $parentName = 'Pendapatan Pasien Umum';
                        $subName = trim($mainHeaders[$col]);
                        $payerName = 'Pasien Umum';
                    } elseif ($col >= 22 && $col <= 33) {
                        $parentName = 'Rekanan';
                        $subName = isset($subHeaders[$col]) ? trim($subHeaders[$col]) : '';
                        $payerName = $subName;
                    } elseif ($col == 34) {
                        $parentName = 'BPJS Kesehatan';
                        $payerName = 'BPJS Kesehatan';
                    } elseif ($col >= 35 && $col <= 36) {
                        $parentName = 'Pendapatan Lain-lain';
                        $subName = isset($subHeaders[$col]) ? trim($subHeaders[$col]) : '';
                        $payerName = $subName;
                    }

                    if (empty($parentName)) {
                        continue;
                    }

                    $amountString = trim($row[$col]);
                    if (empty($amountString) || $amountString === '-' || $amountString === 'Rp-') {
                        continue;
                    }

                    $amountString = preg_replace('/[^0-9]/', '', explode(',', $amountString)[0]);
                    $amount = (float)$amountString;

                    if ($amount > 0) {
                        $parentType = $receiptTypes->where('name', $parentName)->whereNull('parent_id')->first();
                        if (!$parentType && $parentName === 'Pendapatan Pasien Umum') {
                            $parentType = $receiptTypes->where('name', 'Pendapatan Pasien Umum')->first();
                        }
                        
                        if (!$parentType) {
                            throw new Exception("Kategori penerimaan '{$parentName}' tidak ditemukan di database. Pastikan master data sudah sesuai.");
                        }
                        
                        $parentTypeId = $parentType->id; 

                        $subTypeId = null;
                        if (!empty($subName)) {
                            $subType = $receiptTypes->filter(function ($t) use ($subName, $parentTypeId) {
                                $dbName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $t->name));
                                $searchName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $subName));
                                return $t->parent_id == $parentTypeId &&
                                    (str_contains($dbName, $searchName) || str_contains($searchName, $dbName));
                            })->first();
                            
                            if ($subType) {
                                $subTypeId = $subType->id;
                            } else {
                                throw new Exception("Sub-kategori penerimaan '{$subName}' untuk kategori '{$parentName}' tidak ditemukan di database. Pastikan master data sudah sesuai.");
                            }
                        }

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
