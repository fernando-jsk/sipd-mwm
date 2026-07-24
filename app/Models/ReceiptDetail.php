<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ReceiptDetail extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['amount', 'account_code_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('receipt_detail')
            ->setDescriptionForEvent(fn(string $eventName) => "Aksi {$eventName} pada rincian penerimaan");
    }

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    public function accountCode()
    {
        return $this->belongsTo(AccountCode::class);
    }

    public function fundingSource()
    {
        return $this->belongsTo(FundingSource::class);
    }
}
