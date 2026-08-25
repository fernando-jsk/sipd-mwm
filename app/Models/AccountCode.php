<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AccountCode extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = ['parent_id', 'level', 'code', 'name', 'description', 'is_active', 'funding_source_id'];
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'level' => 'integer',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(AccountCode::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AccountCode::class, 'parent_id');
    }

    public function rbaDocuments(): HasMany
    {
        return $this->hasMany(RbaDocument::class);
    }

    public function fundingSource()
    {
        return $this->belongsTo(FundingSource::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['parent_id', 'level', 'code', 'name', 'description', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('account_code')
            ->setDescriptionForEvent(function (string $eventName) {
                $action = match ($eventName) {
                    'created' => 'Menambahkan',
                    'updated' => 'Memperbarui',
                    'deleted' => 'Menghapus',
                    default => $eventName,
                };
                return "{$action} kode rekening: {$this->code}";
            });
    }

    /**
     * Get the normal balance (Debit/Kredit) based on the account code prefix.
     * 1=Aset(D), 2=Kewajiban(K), 3=Ekuitas(K), 4=Pendapatan LRA(K), 5=Belanja(D), 
     * 6.1=Penerimaan Pembiayaan(K), 6.2=Pengeluaran Pembiayaan(D)
     * 7=Pendapatan LO(K), 8=Beban LO(D), 9=Surplus/Defisit(K)
     */
    public function getNormalBalance(): string
    {
        $firstDigit = substr($this->code, 0, 1);
        $prefixTwo = substr($this->code, 0, 3); // e.g., "6.1"

        return match ($firstDigit) {
            '1' => 'D',
            '2', '3', '4' => 'K',
            '5' => 'D',
            '6' => ($prefixTwo === '6.1') ? 'K' : 'D',
            '7' => 'K',
            '8' => 'D',
            '9' => 'K',
            default => 'D',
        };
    }
}
