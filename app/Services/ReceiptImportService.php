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
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            throw new Exception('File CSV kosong.');
        }

        // Deteksi delimiter (; atau ,) secara otomatis
        $delimiter = ';';
        foreach ($lines as $line) {
            $semicolons = substr_count($line, ';');
            $commas = substr_count($line, ',');
            if ($semicolons > 0 || $commas > 0) {
                $delimiter = $semicolons >= $commas ? ';' : ',';
                break;
            }
        }

        $data = array_map(function ($v) use ($delimiter) {
            return str_getcsv($v, $delimiter);
        }, $lines);

        if (count($data) < 2) {
            throw new Exception('Format file CSV tidak valid. Tidak ada baris data yang ditemukan.');
        }

        // Temukan baris data pertama secara dinamis berdasarkan pola tanggal di kolom 1
        $firstDataRowIndex = null;
        for ($i = 0; $i < count($data); $i++) {
            $dateCandidate = isset($data[$i][1]) ? trim($data[$i][1]) : '';
            if (!empty($dateCandidate) && (
                \DateTime::createFromFormat('d/m/Y', $dateCandidate) ||
                \DateTime::createFromFormat('Y-m-d', $dateCandidate) ||
                \DateTime::createFromFormat('d-m-Y', $dateCandidate)
            )) {
                $firstDataRowIndex = $i;
                break;
            }
        }

        // Tentukan baris header utama dan sub-header
        $mainHeaders = [];
        $subHeaders = [];

        if ($firstDataRowIndex !== null && $firstDataRowIndex >= 7) {
            // Layout standar SIMRS (main di baris 4, sub di baris 6, data mulai baris 7)
            $mainHeaders = $data[4] ?? [];
            $subHeaders = $data[6] ?? [];
        } elseif ($firstDataRowIndex !== null && $firstDataRowIndex >= 2) {
            // Layout terkompresi / modifikasi Excel
            $mainHeaders = $data[$firstDataRowIndex - 2] ?? [];
            $subHeaders = $data[$firstDataRowIndex - 1] ?? [];
        } elseif ($firstDataRowIndex !== null && $firstDataRowIndex == 1) {
            // Hanya 1 baris header tepat di atas data
            $mainHeaders = $data[0] ?? [];
            $subHeaders = [];
        } else {
            // Fallback default
            $mainHeaders = $data[4] ?? ($data[0] ?? []);
            $subHeaders = $data[6] ?? ($data[1] ?? []);
        }

        $receiptTypes = ReceiptType::all();
        $createdReceiptsCount = 0;

        DB::beginTransaction();
        try {
            // 1. Pemetaan Header Dinamis dengan Resolusi Cerdas (Smart Hierarchical Matching)
            $columnMap = [];
            $currentMainHeader = '';
            
            $maxCols = max(count($mainHeaders), count($subHeaders));
            
            for ($col = 2; $col < $maxCols; $col++) {
                $mainVal = isset($mainHeaders[$col]) ? trim($mainHeaders[$col]) : '';
                if (!empty($mainVal)) {
                    $currentMainHeader = $mainVal;
                }
                
                $subVal = isset($subHeaders[$col]) ? trim($subHeaders[$col]) : '';
                
                // Abaikan jika kolom merupakan ringkasan total
                if (stripos($currentMainHeader, 'total') !== false || stripos($subVal, 'total') !== false) {
                    continue;
                }
                
                if (empty($currentMainHeader) && empty($subVal)) {
                    continue; 
                }

                // Resolusi Kategori:
                // Langkah 1: Cek apakah $subVal cocok dengan ReceiptType yang ada di database
                $matchedSub = null;
                if (!empty($subVal)) {
                    $matchedSub = $receiptTypes->first(fn($t) => strtolower(trim($t->name)) === strtolower($subVal));
                }

                // Langkah 2: Cek apakah $currentMainHeader cocok dengan ReceiptType yang ada di database
                $matchedMain = null;
                if (!empty($currentMainHeader)) {
                    $matchedMain = $receiptTypes->first(fn($t) => strtolower(trim($t->name)) === strtolower($currentMainHeader));
                }

                $parentTypeId = null;
                $subTypeId = null;
                $payerName = '';

                if ($matchedSub) {
                    // Jika sub-header cocok dengan ReceiptType yang sudah terdaftar
                    if ($matchedSub->parent_id) {
                        $parentTypeId = $matchedSub->parent_id;
                        $subTypeId = $matchedSub->id;
                    } else {
                        $parentTypeId = $matchedSub->id;
                        $subTypeId = null;
                    }
                    $payerName = $matchedSub->name;
                } elseif ($matchedMain) {
                    // Jika main-header cocok dengan ReceiptType yang sudah terdaftar
                    if ($matchedMain->parent_id) {
                        // Kasus di mana sub-jenis (seperti PX DR UMUM) terbaca di mainHeaders:
                        // Ambil parent_id sebagai jenis utama dan matchedMain sebagai sub-jenis
                        $parentTypeId = $matchedMain->parent_id;
                        $subTypeId = $matchedMain->id;
                        $payerName = !empty($subVal) ? $subVal : $matchedMain->name;
                    } else {
                        // matchedMain adalah kategori induk utama (misal: PASIEN UMUM, PIHAK III, BPJS KESEHATAN)
                        $parentTypeId = $matchedMain->id;
                        $payerName = $matchedMain->name;

                        if (!empty($subVal)) {
                            $payerName = $subVal;
                            // Cari sub-kategori yang terdaftar di bawah parent ini
                            $sub = $receiptTypes->first(fn($t) => $t->parent_id == $parentTypeId && strtolower(trim($t->name)) === strtolower($subVal));
                            if (!$sub) {
                                // Cek apakah nama sub sudah terdaftar di tempat lain untuk mencegah duplicate entry
                                $existingSub = $receiptTypes->first(fn($t) => strtolower(trim($t->name)) === strtolower($subVal));
                                if ($existingSub) {
                                    $subTypeId = $existingSub->id;
                                    $parentTypeId = $existingSub->parent_id ?? $parentTypeId;
                                } else {
                                    $sub = ReceiptType::create([
                                        'name' => $subVal,
                                        'parent_id' => $parentTypeId,
                                        'is_active' => true,
                                        'created_by' => Auth::id() ?? 1,
                                    ]);
                                    $receiptTypes->push($sub);
                                    $subTypeId = $sub->id;
                                }
                            } else {
                                $subTypeId = $sub->id;
                            }
                        }
                    }
                } else {
                    // Jika kategori benar-benar baru dan belum terdaftar di database
                    $existing = $receiptTypes->first(fn($t) => strtolower(trim($t->name)) === strtolower($currentMainHeader));
                    if ($existing) {
                        $parentTypeId = $existing->parent_id ?? $existing->id;
                        $subTypeId = $existing->parent_id ? $existing->id : null;
                        $payerName = !empty($subVal) ? $subVal : $existing->name;
                    } else {
                        $parent = ReceiptType::create([
                            'name' => $currentMainHeader,
                            'is_active' => true,
                            'created_by' => Auth::id() ?? 1,
                        ]);
                        $receiptTypes->push($parent);
                        $parentTypeId = $parent->id;
                        $payerName = $parent->name;

                        if (!empty($subVal)) {
                            $payerName = $subVal;
                            $sub = ReceiptType::create([
                                'name' => $subVal,
                                'parent_id' => $parentTypeId,
                                'is_active' => true,
                                'created_by' => Auth::id() ?? 1,
                            ]);
                            $receiptTypes->push($sub);
                            $subTypeId = $sub->id;
                        }
                    }
                }

                if ($parentTypeId) {
                    $columnMap[$col] = [
                        'parent_id' => $parentTypeId,
                        'sub_id' => $subTypeId,
                        'payer_name' => $payerName ?: $currentMainHeader,
                    ];
                }
            }

            // 2. Membaca Data Transaksi secara Dinamis
            $startRow = $firstDataRowIndex ?? 7;
            for ($i = $startRow; $i < count($data); $i++) {
                $row = $data[$i];
                if (!isset($row[1]) || empty(trim($row[1])) || stripos($row[1], 'total') !== false || stripos($row[1], 'jasa') !== false) {
                    continue;
                }

                $tanggalString = trim($row[1]);
                $dateObj = \DateTime::createFromFormat('d/m/Y', $tanggalString) ?:
                           \DateTime::createFromFormat('Y-m-d', $tanggalString) ?:
                           \DateTime::createFromFormat('d-m-Y', $tanggalString);

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
                    $defaultAccountId = null;
                    if ($group['sub_id']) {
                        $defaultAccountId = $receiptTypes->firstWhere('id', $group['sub_id'])?->account_code_id;
                    }
                    if (!$defaultAccountId && $group['parent_id']) {
                        $defaultAccountId = $receiptTypes->firstWhere('id', $group['parent_id'])?->account_code_id;
                    }

                    $receipt = Receipt::create([
                        'document_number' => null,
                        'date' => $formattedDate,
                        'receipt_type_id' => $group['parent_id'],
                        'receipt_sub_type_id' => $group['sub_id'],
                        'description' => $group['payer_name'],
                        'payer_name' => $group['payer_name'],
                        'payment_method' => 'tunai',
                        'status' => 'draft', // Bypass observer during creation
                        'treasurer_id' => Auth::id(),
                        'created_by' => Auth::id(),
                    ]);

                    foreach ($group['details'] as $detail) {
                        $receipt->details()->create([
                            'account_code_id' => $defaultAccountId,
                            'amount' => $detail['amount']
                        ]);
                    }

                    // Trigger Observer after relations are saved
                    if ($status !== 'draft') {
                        $receipt->status = $status;
                        $receipt->save();
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
