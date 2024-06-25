<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversionRates extends Model
{	
    protected $table = 'conversionrates';

    protected $fillable = [
        'basecurrency', 'convertcurrency','conversionrate'
    ];
}
