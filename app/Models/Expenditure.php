<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Expenditure extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'activity_date' => 'date',
        'opd_date' => 'date',
        'spd_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'document_number', 'date', 'type', 'description', 'status',
                'treasurer_id', 'kpa_id', 'ptk_id', 'activity_date', 'activity_description',
                'payment_method', 'vendor_id', 'bank_name', 'bank_account_number', 'contract_number',
                'opd_number', 'opd_date', 'opd_notes', 'opd_authorized_by',
                'spd_number', 'spd_date', 'spd_disbursed_by'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('expenditure')
            ->setDescriptionForEvent(fn(string $eventName) => "Aksi {$eventName} pada dokumen pengeluaran {$this->document_number}");
    }

    public function details()
    {
        return $this->hasMany(ExpenditureDetail::class);
    }

    public function taxes()
    {
        return $this->hasMany(ExpenditureTax::class);
    }

    public function treasurer()
    {
        return $this->belongsTo(User::class, 'treasurer_id');
    }

    public function kpa()
    {
        return $this->belongsTo(User::class, 'kpa_id');
    }

    public function ptk()
    {
        return $this->belongsTo(User::class, 'ptk_id');
    }

    public function opdAuthorizedBy()
    {
        return $this->belongsTo(User::class, 'opd_authorized_by');
    }

    public function spdDisbursedBy()
    {
        return $this->belongsTo(User::class, 'spd_disbursed_by');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
