<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ReceiptType extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'is_active', 'account_code_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('receipt_type')
            ->setDescriptionForEvent(fn(string $eventName) => "Aksi {$eventName} pada jenis penerimaan {$this->name}");
    }

    public function parent()
    {
        return $this->belongsTo(ReceiptType::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ReceiptType::class, 'parent_id');
    }

    public function accountCode()
    {
        return $this->belongsTo(AccountCode::class, 'account_code_id');
    }
}
