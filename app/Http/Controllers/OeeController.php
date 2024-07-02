<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OeeData;

class OeeController extends Controller
{
    public function index() {
        $data = OeeData::all();
        return view('oee.index', compact('data'));
    }
}