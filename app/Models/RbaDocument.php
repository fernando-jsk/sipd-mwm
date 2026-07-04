<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class RbaDocument extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['account_code_id', 'budget_year', 'version', 'status', 'total_budget'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('rba_document')
            ->setDescriptionForEvent(function (string $eventName) {
                $action = match ($eventName) {
                    'created' => 'Membuat',
                    'updated' => 'Memperbarui',
                    'deleted' => 'Menghapus',
                    default => $eventName,
                };
                return "{$action} Dokumen RBA";
            });
    }

    public function accountCode(): BelongsTo
    {
        return $this->belongsTo(AccountCode::class);
    }

    public function rbaDetails(): HasMany
    {
        return $this->hasMany(RbaDetail::class);
    }

    public function recalculateTotalBudget(): void
    {
        $this->total_budget = $this->rbaDetails()->where('type', 'item')->sum('jumlah');
        $this->save();
    }
}
