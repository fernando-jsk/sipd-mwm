<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'active_budget_year',
                'value' => date('Y'),
                'type' => 'string',
                'description' => 'Tahun anggaran aktif yang digunakan dalam sistem.'
            ],
            [
                'key' => 'budget_validation_type',
                'value' => 'warning',
                'type' => 'string',
                'description' => 'Tipe validasi pagu (warning / block) saat pengeluaran melebihi anggaran.'
            ]
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
