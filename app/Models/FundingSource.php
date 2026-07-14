<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundingSource extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description'];

    public function accountCodes()
    {
        return $this->hasMany(AccountCode::class);
    }

    public function rbaDocuments()
    {
        return $this->hasMany(RbaDocument::class);
    }
}
