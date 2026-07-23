<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ExpenditureTax extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tax_type', 'billing_code', 'amount'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('expenditure_tax')
            ->setDescriptionForEvent(fn(string $eventName) => "Aksi {$eventName} pada pajak pengeluaran");
    }

    public function expenditure()
    {
        return $this->belongsTo(Expenditure::class);
    }
}
