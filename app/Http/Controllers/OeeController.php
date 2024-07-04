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

    public function getData() {
        $data = OeeData::all();
        return response()->json($data);
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

    public function calculatePerformance() {
        // Definisikan Standar Item Per Menit (SM)
        $standardItemsPerMinute = [
            "BACKING PLATE D74A RH/LH 1/4" => 10,
            "BACKING PLATE D74A RH/LH 2/4" => 0.067,
            "BOTTOM PLATE VOLVO 1/2" => 5.88,
            "BRACKER HELPER SPRING BY 4/4" => 5,
            "C/M CAB VOLVO (6624) 1/2" => 7.69,
            "CROSS MEMBER NO.4 LOWER 2/2 0W050" => 5,
            "PARKING BRAKE 650A LH 1/4" => 10,
            "PARKING BRAKE 650A LH 3/4" => 12.5,
            "PARKING BRAKE 650A RH 1/4" => 10,
            "PLATE D - VOLVO 1/2" => 10,
            "PLATE D - VOLVO 2/2" => 7.69,
        ];

        // Ambil semua data dari tabel oee_dashboard
        $data = OeeData::orderBy('timestamp')->get();

        $performanceData = [];
        $currentMinute = null;
        $minuteData = [];

        foreach ($data as $datum) {
            $timestamp = Carbon::parse($datum->timestamp);
            $minute = $timestamp->format('Y-m-d H:i');

            if ($currentMinute !== $minute) {
                if (!empty($minuteData)) {
                    $performanceData[] = $this->calculateMinutePerformance($minuteData, $standardItemsPerMinute);
                }

                $currentMinute = $minute;
                $minuteData = [];
            }

            $minuteData[] = $datum;
        }

        // Hitung performa untuk menit terakhir
        if (!empty($minuteData)) {
            $performanceData[] = $this->calculateMinutePerformance($minuteData, $standardItemsPerMinute);
        }



        return response()->json([
            'performance' => end($performanceData),
            'performarray' => $performanceData
        ]);
    }

    private function calculateMinutePerformance($minuteData, $standardItemsPerMinute)
    {
        $totalPerformance = 0;
        $totalItemsProduced = count($minuteData);
        $itemCounts = [];

        foreach ($minuteData as $datum) {
            $item = $datum->item;
            if (!isset($itemCounts[$item])) {
                $itemCounts[$item] = 0;
            }
            $itemCounts[$item]++;
        }

        foreach ($itemCounts as $item => $count) {
            if (isset($standardItemsPerMinute[$item])) {
                $sm = $standardItemsPerMinute[$item];
                $relativePerformance = ($count / $sm) * 100;
                $totalPerformance += $relativePerformance * ($count / $totalItemsProduced);
            }
        }

        return $totalPerformance;
    }

    public function store(Request $request) {
        // Simpan data OEE ke database
    $oeeData = OeeData::create($request->all());

    // Trigger event
    event(new OeeData($oeeData));

    return response()->json(['success' => true]);
    }
}