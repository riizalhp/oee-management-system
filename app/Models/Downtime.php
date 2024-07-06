<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Downtime extends Model
{
    use HasFactory;
    protected $fillable = ['downtimeid', 'downtimedesc', 'mulai', 'selesai', 'duration', 'downtimeTotal'];
}