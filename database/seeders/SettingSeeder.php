<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Models\AccountCode;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $receiptAccount = AccountCode::where('code', '1.1.01.01')->first();
        $expenditureAccount = AccountCode::where('code', '1.1.01.02')->first();

        $settings = [
            [
                'key' => 'default_receipt_account',
                'value' => $receiptAccount ? $receiptAccount->id : null,
                'type' => 'string',
                'description' => 'ID Kode Akun Kas Default untuk Penerimaan (Jurnal Otomatis)'
            ],
            [
                'key' => 'default_expenditure_account',
                'value' => $expenditureAccount ? $expenditureAccount->id : null,
                'type' => 'string',
                'description' => 'ID Kode Akun Kas Default untuk Pengeluaran (Jurnal Otomatis)'
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
