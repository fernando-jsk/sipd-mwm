<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ExpenditureReceipt extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];

    protected $appends = ['net_amount'];

    public function getNetAmountAttribute()
    {
        return (float) $this->amount - (float) $this->tax_amount;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'receipt_number',
                'date',
                'account_code_id',
                'recipient_name',
                'description',
                'amount',
                'tax_type',
                'tax_amount',
                'billing_code',
                'status',
                'expenditure_id'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('expenditure_receipt')
            ->setDescriptionForEvent(fn(string $eventName) => "Aksi {$eventName} pada kuitansi belanja {$this->receipt_number}");
    }

    public function accountCode()
    {
        return $this->belongsTo(AccountCode::class);
    }

    public function expenditure()
    {
        return $this->belongsTo(Expenditure::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
