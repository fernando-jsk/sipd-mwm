<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class RbaCashAllocation extends Model
{
    use LogsActivity;

    protected $fillable = [
        'rba_document_id',
        'month',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'amount' => 'decimal:2',
        ];
    }

    public function rbaDocument(): BelongsTo
    {
        return $this->belongsTo(RbaDocument::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['rba_document_id', 'month', 'amount'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('rba_cash_allocation')
            ->setDescriptionForEvent(function (string $eventName) {
                $action = match ($eventName) {
                    'created' => 'Menambahkan',
                    'updated' => 'Memperbarui',
                    'deleted' => 'Menghapus',
                    default => $eventName,
                };
                return "{$action} alokasi anggaran kas bulan {$this->month}";
            });
    }
}
