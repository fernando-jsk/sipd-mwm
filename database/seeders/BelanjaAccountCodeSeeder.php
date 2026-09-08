<?php

namespace Database\Seeders;

use App\Models\AccountCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BelanjaAccountCodeSeeder extends Seeder
{
    /**
     * Run the database seeds for Belanja (Kode 5).
     */
    public function run(): void
    {
        $dataFile = __DIR__ . '/data/belanja_account_codes.json';

        if (!File::exists($dataFile)) {
            $this->command->error("File data tidak ditemukan: {$dataFile}");
            return;
        }

        $accounts = json_decode(File::get($dataFile), true);

        if (!is_array($accounts)) {
            $this->command->error("Format JSON tidak valid pada: {$dataFile}");
            return;
        }

        $total = count($accounts);
        $this->command->info("Memulai import {$total} kode rekening Belanja (Kode 5)...");

        // Disable model events (termasuk activity log) agar proses import cepat dan tidak memenuhi log
        AccountCode::withoutEvents(function () use ($accounts, $total) {
            DB::beginTransaction();

            try {
                // In-memory mapping [code => id] untuk query parent_id yang instan (O(1))
                $codeMap = AccountCode::pluck('id', 'code')->toArray();

                $inserted = 0;
                $updated = 0;

                $progressBar = $this->command->getOutput()->createProgressBar($total);
                $progressBar->start();

                foreach ($accounts as $account) {
                    $code = $account['code'];
                    $level = (int) $account['level'];
                    $name = $account['name'];
                    $description = $account['description'] ?? null;

                    $parentId = null;
                    if (str_contains($code, '.')) {
                        $parentCode = substr($code, 0, strrpos($code, '.'));
                        $parentId = $codeMap[$parentCode] ?? null;
                    }

                    $exists = isset($codeMap[$code]);

                    $model = AccountCode::updateOrCreate(
                        ['code' => $code],
                        [
                            'parent_id'   => $parentId,
                            'level'       => $level,
                            'name'        => $name,
                            'description' => $description,
                            'is_active'   => true,
                        ]
                    );

                    $codeMap[$code] = $model->id;

                    if ($exists) {
                        $updated++;
                    } else {
                        $inserted++;
                    }

                    $progressBar->advance();
                }

                $progressBar->finish();
                DB::commit();

                $this->command->newLine(2);
                $this->command->info("✓ Selesai mengimpor kode rekening Belanja!");
                $this->command->line("  - Ditambahkan : {$inserted}");
                $this->command->line("  - Diperbarui  : {$updated}");
                $this->command->line("  - Total       : {$total}");
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->command->newLine();
                $this->command->error("Terjadi kesalahan saat import: " . $e->getMessage());
                throw $e;
            }
        });
    }
}
