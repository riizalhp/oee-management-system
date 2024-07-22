<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineStartTime extends Model
{
    use HasFactory;

    protected $fillable = ['start_prod', 'finish_prod', 'worktime'];
}