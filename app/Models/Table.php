<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'ТИФ_ТН',
        'Товар_номи',
        'Ўлчов_бирлиги',
        'Қўшимча_ўлчов_бирлиги'
    ];
}
