<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsefulLink extends Model
{
    use HasFactory;

    protected $table = 'useful_links';

    protected $fillable = [
        'icon',
        'title',
        'link',
        'description',
        'position',
    ];
}
