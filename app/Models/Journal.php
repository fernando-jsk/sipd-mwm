<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Journal extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['date', 'reference_no', 'description', 'type', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('journal')
            ->setDescriptionForEvent(function (string $eventName) {
                $typeName = match ($this->type) {
                    'opening_balance' => 'Saldo Awal',
                    'adjustment' => 'Jurnal Penyesuaian',
                    'closing' => 'Jurnal Penutup',
                    default => 'Jurnal Umum',
                };
                return "Aksi {$eventName} pada {$typeName} {$this->reference_no}";
            });
    }

    public function details()
    {
        return $this->hasMany(JournalDetail::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalable()
    {
        return $this->morphTo();
    }
}
