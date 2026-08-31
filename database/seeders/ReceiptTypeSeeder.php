<?php

namespace Database\Seeders;

use App\Models\AccountCode;
use App\Models\ReceiptType;
use Illuminate\Database\Seeder;

class ReceiptTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cari akun pendapatan '4.1' (Jasa Layanan) atau akun pendapatan kepala 4 terdekat
        $defaultAccount = AccountCode::where('code', '4.1')->first()
            ?? AccountCode::where('code', 'like', '4%')->orderBy('code')->first();
        $accountCodeId = $defaultAccount?->id;

        $categories = [
            [
                'name' => 'PASIEN UMUM',
                'description' => 'Penerimaan dari pelayanan pasien umum',
                'is_active' => true,
                'children' => [
                    'PX DR UMUM',
                    'PX DR SPESIALIS',
                    'LAB',
                    'IGD',
                    'IRDO',
                    'DOKUMEN REKAM MEDIK',
                    'STATUS',
                    'APOTEK',
                    'OK',
                    'HC',
                    'EKG',
                    'ECHO/ TREADMILL',
                    'PERSALINAN',
                    'RONTGEN/CT. SCAN',
                    'USG',
                    'AMBULANCE',
                    'PENDAPATAN RAWAT INAP',
                    'HYPERBARIC',
                    'MMPI',
                ]
            ],
            [
                'name' => 'PIHAK III',
                'description' => 'Penerimaan kerja sama pihak ketiga dan sewa lahan',
                'is_active' => true,
                'children' => [
                    'KIMIA FARMA',
                    'TOKO KEMBANG VALENTINO',
                    'BPJS Ketenagakerjaan',
                    'Jasa Raharja',
                    'PT WIKA',
                    'PT IQVIA',
                    'Sewa Lahan Kantin oma yul',
                    'Sewa Lahan Kantin Olivia',
                    'Sewa Lahan BRI',
                    'CentrePark',
                    'Sekretariat DPRD',
                    'DINKES',
                ]
            ],
            [
                'name' => 'BPJS KESEHATAN',
                'description' => 'Klaim dan penerimaan BPJS Kesehatan',
                'is_active' => true,
                'children' => []
            ],
            [
                'name' => 'PENDAPATAN LAIN-LAIN',
                'description' => 'Pendapatan operasional lain-lain',
                'is_active' => true,
                'children' => [
                    'Steril Alat',
                    'Lain-Lain',
                ]
            ],
        ];

        foreach ($categories as $category) {
            $parent = ReceiptType::updateOrCreate(
                ['name' => $category['name'], 'parent_id' => null],
                [
                    'description' => $category['description'] ?? null,
                    'is_active' => $category['is_active'] ?? true,
                    'account_code_id' => $accountCodeId,
                ]
            );

            foreach ($category['children'] as $childName) {
                ReceiptType::updateOrCreate(
                    ['name' => $childName, 'parent_id' => $parent->id],
                    [
                        'is_active' => true,
                        'account_code_id' => $accountCodeId,
                    ]
                );
            }
        }
    }
}
