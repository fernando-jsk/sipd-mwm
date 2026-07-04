<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class RbaDetail extends Model
{
    use LogsActivity;

    protected $fillable = [
        'rba_document_id',
        'parent_id',
        'type',
        'uraian',
        'vol_1', 'satuan_1',
        'vol_2', 'satuan_2',
        'vol_3', 'satuan_3',
        'vol_4', 'satuan_4',
        'koefisien',
        'satuan',
        'harga',
        'jumlah'
    ];

    protected function casts(): array
    {
        return [
            'vol_1' => 'decimal:2',
            'vol_2' => 'decimal:2',
            'vol_3' => 'decimal:2',
            'vol_4' => 'decimal:2',
            'koefisien' => 'decimal:2',
            'harga' => 'decimal:2',
            'jumlah' => 'decimal:2',
        ];
    }

    public function rbaDocument(): BelongsTo
    {
        return $this->belongsTo(RbaDocument::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(RbaDetail::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(RbaDetail::class, 'parent_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('rba_detail')
            ->setDescriptionForEvent(function (string $eventName) {
                $action = match ($eventName) {
                    'created' => 'Menambahkan',
                    'updated' => 'Memperbarui',
                    'deleted' => 'Menghapus',
                    default => $eventName,
                };
                return "{$action} rincian RBA: {$this->uraian}";
            });
    }
}
