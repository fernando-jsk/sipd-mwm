<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AccountCode;

class AccountCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // KODE 1: ASET
            ['code' => '1', 'name' => 'ASET', 'level' => 1, 'is_active' => true],
            ['code' => '1.1', 'name' => 'Aset Lancar', 'level' => 2, 'is_active' => true],
            ['code' => '1.1.01', 'name' => 'KAS DAN SETARA KAS', 'level' => 3, 'is_active' => true],
            ['code' => '1.1.01.01', 'name' => 'Kas di Bendahara Penerimaan', 'level' => 4, 'is_active' => true],
            ['code' => '1.1.01.02', 'name' => 'Kas di Bendahara Pengeluaran', 'level' => 4, 'is_active' => true],
            ['code' => '1.1.01.03', 'name' => 'Kas di BLUD', 'level' => 4, 'is_active' => true],
            
            // KODE 2: KEWAJIBAN
            ['code' => '2', 'name' => 'KEWAJIBAN', 'level' => 1, 'is_active' => true],
            ['code' => '2.1', 'name' => 'Kewajiban Jangka Pendek', 'level' => 2, 'is_active' => true],
            ['code' => '2.1.01', 'name' => 'Utang Perhitungan Pihak Ketiga (PFK)', 'level' => 3, 'is_active' => true],
            
            // KODE 3: EKUITAS
            ['code' => '3', 'name' => 'EKUITAS', 'level' => 1, 'is_active' => true],
            ['code' => '3.1', 'name' => 'Ekuitas', 'level' => 2, 'is_active' => true],
            ['code' => '3.1.01', 'name' => 'Ekuitas Akhir', 'level' => 3, 'is_active' => true],
            ['code' => '3.1.02', 'name' => 'Surplus/Defisit-LO', 'level' => 3, 'is_active' => true],
            ['code' => '3.1.03', 'name' => 'Ekuitas SAL', 'level' => 3, 'is_active' => true],

            // KODE 6: PEMBIAYAAN (Opsional tapi baik untuk LPSAL)
            ['code' => '6', 'name' => 'PEMBIAYAAN DAERAH', 'level' => 1, 'is_active' => true],
            ['code' => '6.1', 'name' => 'Penerimaan Pembiayaan', 'level' => 2, 'is_active' => true],
            ['code' => '6.1.01', 'name' => 'Sisa Lebih Perhitungan Anggaran (SiLPA) Tahun Sebelumnya', 'level' => 3, 'is_active' => true],
        ];

        foreach ($accounts as $accountData) {
            $parentId = null;
            if (strpos($accountData['code'], '.') !== false) {
                $parentCode = substr($accountData['code'], 0, strrpos($accountData['code'], '.'));
                $parentId = AccountCode::where('code', $parentCode)->value('id');
            }

            $accountData['parent_id'] = $parentId;
            AccountCode::updateOrCreate(
                ['code' => $accountData['code']],
                $accountData
            );
        }
    }
}
