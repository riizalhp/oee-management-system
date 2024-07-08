<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineStartTime extends Model
{
    use HasFactory;

    protected $fillable = ['machine_start', 'machine_end', 'planned_time'];
}