<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExpenditureDetail;
use App\Models\RbaDocument;
use App\Models\RbaDetail;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class SyncExpenditureRbaDetails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expenditures:sync-rba-details';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Standarisasi teks uraian belanja ke teks resmi RBA dan jalankan matching Tier 2 pada data belum terpetakan.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Memulai sinkronisasi dan standarisasi rincian belanja SPPD...");

        // 1. Standarisasi baris yang sudah memiliki rba_detail_id
        $this->info("Langkah 1: Menstandarisasi baris belanja yang sudah memiliki rba_detail_id...");
        $alreadyMatched = ExpenditureDetail::whereNotNull('rba_detail_id')
            ->with('rbaDetail')
            ->get();

        $standardizedCount = 0;
        foreach ($alreadyMatched as $d) {
            if ($d->rbaDetail && $d->uraian !== $d->rbaDetail->uraian) {
                $d->update(['uraian' => $d->rbaDetail->uraian]);
                $standardizedCount++;
            }
        }
        $this->info("-> Berhasil menstandarisasi {$standardizedCount} baris ke teks resmi RBA.");

        // 2. Jalankan Tier 2 Normalization pada baris yang belum memiliki rba_detail_id
        $this->info("Langkah 2: Menjalankan pembersihan dan matching Tier 2 pada baris belum terpetakan...");

        $unmapped = ExpenditureDetail::whereNull('rba_detail_id')
            ->whereNotNull('uraian')
            ->where('uraian', '!=', '')
            ->with(['expenditure', 'accountCode'])
            ->get();

        if ($unmapped->isEmpty()) {
            $this->info("-> Tidak ada baris belanja yang belum terpetakan.");
            return Command::SUCCESS;
        }

        $rbaCache = [];
        $tier2ResolvedCount = 0;

        foreach ($unmapped as $d) {
            $year = date('Y', strtotime($d->expenditure?->date ?? 'now'));

            if (!isset($rbaCache['version'][$year])) {
                $rbaCache['version'][$year] = (int) (Setting::where('key', "rba_active_version_{$year}")->value('value') 
                    ?? Setting::where('key', 'rba_active_version')->value('value') 
                    ?? 0);
            }
            $activeVersion = $rbaCache['version'][$year];

            $accId = $d->account_code_id;
            if (!isset($rbaCache['doc'][$year][$accId])) {
                $rbaDoc = RbaDocument::where('budget_year', $year)
                    ->where('version', $activeVersion)
                    ->where('account_code_id', $accId)
                    ->first();

                if (!$rbaDoc && $d->accountCode) {
                    $rbaDoc = RbaDocument::where('budget_year', $year)
                        ->where('version', $activeVersion)
                        ->whereHas('accountCode', function ($q) use ($d) {
                            $q->where('name', $d->accountCode->name);
                        })
                        ->first();
                }

                $rbaCache['doc'][$year][$accId] = $rbaDoc;
            }

            $rbaDoc = $rbaCache['doc'][$year][$accId];
            if (!$rbaDoc) {
                continue;
            }

            if (!isset($rbaCache['details'][$rbaDoc->id])) {
                $exactMap = [];
                $cleanMap = [];

                $details = RbaDetail::where('rba_document_id', $rbaDoc->id)->get(['id', 'uraian']);
                foreach ($details as $item) {
                    $rawUraian = trim($item->uraian);
                    $norm = strtolower($rawUraian);
                    $exactMap[$norm] = ['id' => $item->id, 'uraian' => $rawUraian];

                    $cleanPunct = preg_replace('/[^a-z0-9]/', '', $norm);
                    if (!empty($cleanPunct) && !isset($cleanMap[$cleanPunct])) {
                        $cleanMap[$cleanPunct] = ['id' => $item->id, 'uraian' => $rawUraian];
                    }
                }

                $rbaCache['details'][$rbaDoc->id] = [
                    'exact' => $exactMap,
                    'clean' => $cleanMap
                ];
            }

            $lookup = $rbaCache['details'][$rbaDoc->id];
            $normExcel = strtolower(trim($d->uraian));

            $matched = null;
            if (isset($lookup['exact'][$normExcel])) {
                $matched = $lookup['exact'][$normExcel];
            } else {
                $cleanText = $normExcel;
                $cleanText = preg_replace('/^pembayaran\s+/i', '', $cleanText);
                $cleanText = preg_replace('/\(.*?\)/', '', $cleanText);
                $cleanText = preg_replace('/\b(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember)\b.*$/i', '', $cleanText);
                $cleanText = preg_replace('/\b202[0-9]\b/', '', $cleanText);
                $cleanText = trim($cleanText, " \t\n\r\0\x0B-/,");
                $cleanNoPunct = preg_replace('/[^a-z0-9]/', '', $cleanText);

                if (!empty($cleanNoPunct) && isset($lookup['clean'][$cleanNoPunct])) {
                    $matched = $lookup['clean'][$cleanNoPunct];
                }
            }

            if ($matched) {
                $d->update([
                    'rba_detail_id' => $matched['id'],
                    'uraian' => $matched['uraian']
                ]);
                $tier2ResolvedCount++;
            }
        }

        $this->info("-> Berhasil memetakan {$tier2ResolvedCount} baris tambahan melalui pembersihan Tier 2.");

        $remainingCount = ExpenditureDetail::whereNull('rba_detail_id')
            ->whereNotNull('uraian')
            ->where('uraian', '!=', '')
            ->count();

        $this->newLine();
        $this->info("=== RINGKASAN HASIL AKHIR ===");
        $this->info("Total Baris Standar RBA: " . ($alreadyMatched->count() + $tier2ResolvedCount));
        $this->info("Sisa Baris Belum Terpetakan: {$remainingCount}");
        $this->info("Sisa baris dapat diselaraskan secara massal melalui antarmuka web.");

        return Command::SUCCESS;
    }
}
