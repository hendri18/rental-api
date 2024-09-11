<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    protected $table = 'cars';

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return url($this->image);
    }
}
