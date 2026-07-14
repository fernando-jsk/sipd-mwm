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
}
