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

        $kasBlud = AccountCode::where('code', '1.1.01.03')->first() ?? $expenditureAccount;
        $kasBendaharaPengeluaran = AccountCode::where('code', '1.1.01.02')->first() ?? $expenditureAccount;

        $defaultRules = [
            'UP' => [
                'code' => 'UP',
                'name' => 'Uang Persediaan (UP)',
                'credit_account_id' => $kasBlud ? (string) $kasBlud->id : null,
                'debit_account_id' => $kasBendaharaPengeluaran ? (string) $kasBendaharaPengeluaran->id : null,
                'is_budgetary' => false,
                'description' => 'Uang Muka Kerja (Mutasi Kas Bank ke Kas Bendahara Pengeluaran)'
            ],
            'GU' => [
                'code' => 'GU',
                'name' => 'Ganti Uang (GU)',
                'credit_account_id' => $kasBlud ? (string) $kasBlud->id : null,
                'debit_account_id' => null,
                'is_budgetary' => true,
                'description' => 'Reimbursement Belanja Riil atas SPJ Bendahara'
            ],
            'TU' => [
                'code' => 'TU',
                'name' => 'Tambahan Uang (TU)',
                'credit_account_id' => $kasBlud ? (string) $kasBlud->id : null,
                'debit_account_id' => $kasBendaharaPengeluaran ? (string) $kasBendaharaPengeluaran->id : null,
                'is_budgetary' => false,
                'description' => 'Uang Muka Tambahan untuk Kebutuhan Mendesak'
            ],
            'LS_Pegawai' => [
                'code' => 'LS_Pegawai',
                'name' => 'LS Pegawai',
                'credit_account_id' => $kasBlud ? (string) $kasBlud->id : null,
                'debit_account_id' => null,
                'is_budgetary' => true,
                'description' => 'Pembayaran Langsung Belanja Pegawai (Gaji / Jaspel)'
            ],
            'LS_Barang_Jasa_Modal' => [
                'code' => 'LS_Barang_Jasa_Modal',
                'name' => 'LS Barang, Jasa dan Modal',
                'credit_account_id' => $kasBlud ? (string) $kasBlud->id : null,
                'debit_account_id' => null,
                'is_budgetary' => true,
                'description' => 'Pembayaran Langsung Belanja Barang/Jasa/Modal ke Rekanan'
            ],
            'LS' => [
                'code' => 'LS',
                'name' => 'LS Umum',
                'credit_account_id' => $kasBlud ? (string) $kasBlud->id : null,
                'debit_account_id' => null,
                'is_budgetary' => true,
                'description' => 'Pembayaran Langsung (Umum)'
            ],
        ];

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
            [
                'key' => 'expenditure_journal_rules',
                'value' => json_encode($defaultRules),
                'type' => 'json',
                'description' => 'Konfigurasi Pemetaan Akun Jurnal & Sifat Anggaran Berdasarkan Jenis Pengeluaran'
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
