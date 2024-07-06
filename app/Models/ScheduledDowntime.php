<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledDowntime extends Model
{
    use HasFactory;

    protected $fillable = ['start_time', 'end_time'];

    // Optionally, you can define custom date attributes
    protected $dates = ['start_time', 'end_time'];
}