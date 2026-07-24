<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Receipt extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['document_number', 'date', 'description', 'payer_name', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('receipt')
            ->setDescriptionForEvent(fn(string $eventName) => "Aksi {$eventName} pada tanda bukti penerimaan {$this->document_number}");
    }

    public function type()
    {
        return $this->belongsTo(ReceiptType::class, 'receipt_type_id');
    }

    public function subType()
    {
        return $this->belongsTo(ReceiptType::class, 'receipt_sub_type_id');
    }

    public function treasurer()
    {
        return $this->belongsTo(User::class, 'treasurer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details()
    {
        return $this->hasMany(ReceiptDetail::class);
    }
}
