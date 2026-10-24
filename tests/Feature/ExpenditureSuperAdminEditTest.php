<?php

namespace Tests\Feature;

use App\Models\AccountCode;
use App\Models\Expenditure;
use App\Models\ExpenditureDetail;
use App\Models\RbaDocument;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpenditureSuperAdminEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'bendahara']);

        $kas = AccountCode::create([
            'code' => '1.1.01.01.01.0001',
            'name' => 'Kas di Bank',
            'type' => 'asset',
            'is_active' => true,
        ]);
        Setting::updateOrCreate(['key' => 'default_expenditure_account'], ['value' => (string) $kas->id]);
    }

    public function test_non_super_admin_cannot_edit_disbursed_expenditure(): void
    {
        $regularUser = User::factory()->create();
        $regularUser->assignRole('bendahara');

        $expenditure = Expenditure::create([
            'document_number' => 'SPPD-001/2026',
            'date' => '2026-03-01',
            'type' => 'LS_Barang_Jasa_Modal',
            'description' => 'Pengadaan ATK Kantor',
            'status' => 'disbursed',
            'treasurer_id' => $regularUser->id,
            'kpa_id' => $regularUser->id,
            'ptk_id' => $regularUser->id,
            'activity_date' => '2026-03-01',
            'activity_description' => 'Kegiatan Operasional',
            'payment_method' => 'rekanan',
            'created_by' => $regularUser->id,
        ]);

        $response = $this->actingAs($regularUser)->get(route('expenditures.edit', $expenditure->id));

        $response->assertRedirect(route('expenditures.sppd'));
        $response->assertSessionHas('error', 'Hanya dokumen Draft atau Ditolak yang dapat diedit.');
    }

    public function test_super_admin_can_access_edit_for_disbursed_expenditure(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $expenditure = Expenditure::create([
            'document_number' => 'SPPD-002/2026',
            'date' => '2026-03-01',
            'type' => 'LS_Barang_Jasa_Modal',
            'description' => 'Pengadaan ATK Kantor',
            'status' => 'disbursed',
            'treasurer_id' => $superAdmin->id,
            'kpa_id' => $superAdmin->id,
            'ptk_id' => $superAdmin->id,
            'activity_date' => '2026-03-01',
            'activity_description' => 'Kegiatan Operasional',
            'payment_method' => 'rekanan',
            'created_by' => $superAdmin->id,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('expenditures.edit', $expenditure->id));

        $response->assertOk();
    }

    public function test_super_admin_can_update_disbursed_expenditure(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $account = AccountCode::create([
            'code' => '5.1.02.01.01.0024',
            'name' => 'Belanja Alat Tulis Kantor',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $kasAccount = AccountCode::create([
            'code' => '1.1.01.01.01.0001',
            'name' => 'Kas di Bank',
            'type' => 'asset',
            'is_active' => true,
        ]);

        Setting::updateOrCreate(['key' => 'default_expenditure_account'], ['value' => (string) $kasAccount->id]);
        Setting::updateOrCreate(['key' => 'rba_active_version_2026'], ['value' => '1']);

        RbaDocument::create([
            'budget_year' => 2026,
            'version' => 1,
            'rba_type' => 'gelondongan',
            'account_code_id' => $account->id,
            'total_budget' => 50000000,
        ]);

        $expenditure = Expenditure::create([
            'document_number' => 'SPPD-003/2026',
            'date' => '2026-03-01',
            'type' => 'LS_Barang_Jasa_Modal',
            'description' => 'Pengadaan Awal',
            'status' => 'disbursed',
            'treasurer_id' => $superAdmin->id,
            'kpa_id' => $superAdmin->id,
            'ptk_id' => $superAdmin->id,
            'activity_date' => '2026-03-01',
            'activity_description' => 'Kegiatan Operasional',
            'payment_method' => 'rekanan',
            'created_by' => $superAdmin->id,
        ]);

        ExpenditureDetail::create([
            'expenditure_id' => $expenditure->id,
            'account_code_id' => $account->id,
            'amount' => 1000000,
        ]);

        $payload = [
            'document_number' => 'SPPD-003/2026',
            'date' => '2026-03-01',
            'type' => 'LS_Barang_Jasa_Modal',
            'description' => 'Pengadaan Revisi Super Admin',
            'treasurer_id' => $superAdmin->id,
            'kpa_id' => $superAdmin->id,
            'ptk_id' => $superAdmin->id,
            'activity_date' => '2026-03-01',
            'activity_description' => 'Kegiatan Operasional Update',
            'payment_method' => 'rekanan',
            'status' => 'disbursed',
            'details' => [
                [
                    'account_code_id' => $account->id,
                    'amount' => 1500000,
                    'uraian' => 'Kertas A4 dan Tinta',
                ]
            ],
        ];

        $response = $this->actingAs($superAdmin)
            ->withSession(['active_budget_year' => 2026])
            ->put(route('expenditures.update', $expenditure->id), $payload);

        $response->assertRedirect(route('expenditures.sppd'));
        $response->assertSessionHas('message', 'Dokumen SPPD berhasil diperbarui.');

        $this->assertDatabaseHas('expenditures', [
            'id' => $expenditure->id,
            'description' => 'Pengadaan Revisi Super Admin',
            'status' => 'disbursed',
        ]);

        $this->assertDatabaseHas('expenditure_details', [
            'expenditure_id' => $expenditure->id,
            'amount' => 1500000,
            'uraian' => 'Kertas A4 dan Tinta',
        ]);
    }
}
