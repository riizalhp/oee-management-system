<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OeeData;
use Carbon\Carbon;

class OeeController extends Controller
{
    public function index() {
        // Ambil semua data dari tabel oee_dashboard
        $data = OeeData::orderBy('id', 'asc')->get();

        return view('oee.index', compact('data'));
    }

    public function calculateAvailability()
    {
        // Ambil semua data dari tabel oee_dashboard
        $data = OeeData::orderBy('timestamp')->get();

        $downtime = 0;
        $lastTimestamp = null;
        $startOfDay = Carbon::now('Asia/Jakarta')->startOfDay();
        $currentTime = Carbon::now('Asia/Jakarta');
        $totalMinutesElapsed = $startOfDay->diffInMinutes($currentTime);

        foreach ($data as $datum) {
            $currentTimestamp = Carbon::parse($datum->timestamp);

            if ($lastTimestamp) {
                $interval = $lastTimestamp->diffInMinutes($currentTimestamp);

                if ($interval > 3) {
                    $downtime += $interval;
                }
            } else {
                // Di awal ketika mesin menyala sampai produksi pertama dihitung sebagai downtime
                $downtime += $currentTimestamp->diffInMinutes($startOfDay);
            }

            $lastTimestamp = $currentTimestamp;
        }

        // Tambahkan downtime jika lebih dari 3 menit belum ada produksi
        if ($lastTimestamp) {
            $interval = $lastTimestamp->diffInMinutes($currentTime);

            if ($interval > 3) {
                $downtime += $interval;
            }
        }

        $operatingTime = $totalMinutesElapsed - $downtime;
        $availability = ($operatingTime / 1440) * 100;

        return response()->json([
            'availability' => $availability,
            'downtime' => $downtime,
            'operatingTime' => $operatingTime
        ]);
    }
}