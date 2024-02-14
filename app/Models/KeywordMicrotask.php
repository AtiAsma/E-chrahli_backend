<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeywordMicrotask extends Model
{
    use HasFactory;

    protected $fillable = ['keyword_id', 'microtask'];
}
