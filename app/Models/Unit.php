<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'units';

    public $timestamps = true;

    protected $fillable = [
        'unit_code',
        'unit_abbreviation',
        'unit_name',
        'unit_tax_code',
        'unit_address',
    ];

    public function order(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function profitReport(): HasMany
    {
        return $this->hasMany(ProfitReport::class);
    }

    public function excelUploads(): HasMany
    {
        return $this->hasMany(ExcelUpload::class);
    }
}
