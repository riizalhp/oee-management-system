<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OeeData;
use App\Models\Production;
use App\Models\OeeMetric;
use App\Models\Downtime;
use App\Models\Item;
use App\Models\MachineStartTime;
use App\Models\MachineStatus;
use App\Models\ScheduledDowntime;
use Carbon\Carbon;

class OeeController extends Controller
{
    public function index() {

        // $data = OeeData::orderBy('id', 'asc')->get();

        // return view('oee.index', compact('data'));

        $productions = Production::all();

        // Calculate OEE metrics
        $oeeMetrics = $this->calculateOeeMetrics();

        // Retrieve machine status
        $machineStatus = MachineStatus::latest()->first();
        $status = $machineStatus ? $machineStatus->status : null;

        // Ambil atau buat OEE metric terbaru
        $latestOeeMetric = OeeMetric::latest()->first();
        if (!$latestOeeMetric) {
            $latestOeeMetric = new OeeMetric();
        }
        $latestReject = $latestOeeMetric->reject;

        return view('oee.index', compact('productions', 'oeeMetrics', 'status', 'latestReject'));
    }

    // public function getData() {
    //     $data = OeeData::all();
    //     return response()->json($data);
    // }

    // public function calculateAvailability()
    // {
    //     // Ambil semua data dari tabel oee_dashboard
    //     $data = OeeData::orderBy('timestamp')->get();

    //     $downtime = 0;
    //     $lastTimestamp = null;
    //     $startOfDay = Carbon::now('Asia/Jakarta')->startOfDay();
    //     $currentTime = Carbon::now('Asia/Jakarta');
    //     $totalMinutesElapsed = $startOfDay->diffInMinutes($currentTime);

    //     foreach ($data as $datum) {
    //         $currentTimestamp = Carbon::parse($datum->timestamp);

    //         if ($lastTimestamp) {
    //             $interval = $lastTimestamp->diffInMinutes($currentTimestamp);

    //             if ($interval > 3) {
    //                 $downtime += $interval;
    //             }
    //         } else {
    //             // Di awal ketika mesin menyala sampai produksi pertama dihitung sebagai downtime
    //             $downtime += $currentTimestamp->diffInMinutes($startOfDay);
    //         }

    //         $lastTimestamp = $currentTimestamp;
    //     }

    //     // Tambahkan downtime jika lebih dari 3 menit belum ada produksi
    //     if ($lastTimestamp) {
    //         $interval = $lastTimestamp->diffInMinutes($currentTime);

    //         if ($interval > 3) {
    //             $downtime += $interval;
    //         }
    //     }

    //     $operatingTime = $totalMinutesElapsed - $downtime;
    //     $availability = ($operatingTime / 1440) * 100;

    //     return response()->json([
    //         'availability' => $availability,
    //         'downtime' => $downtime,
    //         'operatingTime' => $operatingTime
    //     ]);
    // }

    // public function calculatePerformance() {
    //     // Definisikan Standar Item Per Menit (SM)
    //     $standardItemsPerMinute = [
    //         "BACKING PLATE D74A RH/LH 1/4" => 10,
    //         "BACKING PLATE D74A RH/LH 2/4" => 0.067,
    //         "BOTTOM PLATE VOLVO 1/2" => 5.88,
    //         "BRACKER HELPER SPRING BY 4/4" => 5,
    //         "C/M CAB VOLVO (6624) 1/2" => 7.69,
    //         "CROSS MEMBER NO.4 LOWER 2/2 0W050" => 5,
    //         "PARKING BRAKE 650A LH 1/4" => 10,
    //         "PARKING BRAKE 650A LH 3/4" => 12.5,
    //         "PARKING BRAKE 650A RH 1/4" => 10,
    //         "PLATE D - VOLVO 1/2" => 10,
    //         "PLATE D - VOLVO 2/2" => 7.69,
    //     ];

    //     // Ambil semua data dari tabel oee_dashboard
    //     $data = OeeData::orderBy('timestamp')->get();

    //     $performanceData = [];
    //     $startOfDay = Carbon::now('Asia/Jakarta')->startOfDay();
    //     $currentTime = Carbon::now('Asia/Jakarta');
    //     $totalMinutesElapsed = $startOfDay->diffInMinutes($currentTime);
    //     $currentMinute = $startOfDay->copy();
    //     $minuteData = [];

    //     // Iterasi setiap menit dari awal hari sampai sekarang
    //     for ($i = 0; $i <= $totalMinutesElapsed; $i++) {
    //         $minuteData[$currentMinute->format('Y-m-d H:i')] = [];
    //         $currentMinute->addMinute();
    //     }

    //     // Kelompokkan data produksi berdasarkan menit
    //     foreach ($data as $datum) {
    //         $timestamp = Carbon::parse($datum->timestamp, 'Asia/Jakarta');
    //         $minute = $timestamp->format('Y-m-d H:i');
    //         $minuteData[$minute][] = $datum;
    //     }

    //     // Hitung performa untuk setiap menit
    //     foreach ($minuteData as $minute => $dataInMinute) {
    //         if (empty($dataInMinute)) {
    //             $performanceData[] = [
    //                 'minute' => $minute,
    //                 'performance' => 0
    //             ];
    //         } else {
    //             $performanceData[] = [
    //                 'minute' => $minute,
    //                 'performance' => $this->calculateMinutePerformance($dataInMinute, $standardItemsPerMinute)
    //             ];
    //         }
    //     }

    //     return response()->json([
    //         'performance' => end($performanceData)['performance'],
    //         'performarray' => array_column($performanceData, 'performance')
    //     ]);
    // }

    // private function calculateMinutePerformance($minuteData, $standardItemsPerMinute)
    // {
    //     $totalPerformance = 0;
    //     $totalItemsProduced = count($minuteData);
    //     $itemCounts = [];

    //     foreach ($minuteData as $datum) {
    //         $item = $datum->item;
    //         if (!isset($itemCounts[$item])) {
    //             $itemCounts[$item] = 0;
    //         }
    //         $itemCounts[$item]++;
    //     }

    //     foreach ($itemCounts as $item => $count) {
    //         if (isset($standardItemsPerMinute[$item])) {
    //             $sm = $standardItemsPerMinute[$item];
    //             $relativePerformance = ($count / $sm) * 100;
    //             $totalPerformance += $relativePerformance * ($count / $totalItemsProduced);
    //         }
    //     }

    //     return $totalPerformance;
    // }

    // public function store(Request $request) {
    //     // Simpan data OEE ke database
    // $oeeData = OeeData::create($request->all());

    // // Trigger event
    // event(new OeeData($oeeData));

    // return response()->json(['success' => true]);
    // }

    private function calculateOeeMetrics()
    {
        $plannedTime = 575; // Planned time in minutes
        $downtimes = Downtime::all();
        $totalDowntime = $downtimes->sum('duration');

        // Calculate operatingTime
        $runtime = 575; // Example runtime, this should be dynamically calculated
        $operatingTime = $runtime - $totalDowntime;

        // Calculate Availability
        $availability = ($operatingTime / $plannedTime) * 100;

        // Calculate Performance
        $productions = Production::all();
        $totalProducedItems = $productions->count();
        $idealProduceTime = Item::whereIn('nama_item', $productions->pluck('nama_line'))->sum('idealProduceTime');
        $performance = ($idealProduceTime * $totalProducedItems) / $operatingTime * 100;

        // Calculate Quality
        $outputMesin = $totalProducedItems; // Example value
        $latestOeeMetric =  OeeMetric::latest()->first();
        $rejects = $latestOeeMetric ? $latestOeeMetric->reject : 0;

        // Check if $outputMesin is greater than zero to avoid DivisionByZeroError
        if ($outputMesin > 0) {
            $outputStandar = $outputMesin - $rejects;
            $quality = ($outputStandar / $outputMesin) * 100;
        } else {
            // Handle the case where $outputMesin is zero (or less than or equal to zero)
            // For example, set quality to zero or handle it based on your business logic.
            $quality = 0; // or any default value or handling logic
        }

        // Calculate OEE
        $oee = ($availability / 100) * ($performance / 100) * ($quality / 100) * 100;

        // Save OEE metrics to database
        $oeeMetric = new OeeMetric();
        $oeeMetric->availability = $availability;
        $oeeMetric->performance = $performance;
        $oeeMetric->quality = $quality;
        $oeeMetric->reject = $rejects;
        $oeeMetric->oee = $oee;
        $oeeMetric->timestamp = Carbon::now();
        $oeeMetric->save();

        return $oeeMetric;
    }

    public function updateDowntime(Request $request)
    {
        // Validate request data
        $request->validate([
            'downtimeid' => 'required|string',
            'downtimedesc' => 'required|string',
            'mulai' => 'required|date_format:H:i',
            'selesai' => 'nullable|date_format:H:i'
        ]);

        $downtime = new Downtime();
        $downtime->downtimeid = $request->downtimeid;
        $downtime->downtimedesc = $request->downtimedesc;
        $downtime->mulai = $request->mulai;
        $downtime->selesai = $request->selesai;
        if ($request->selesai) {
            $duration = Carbon::parse($request->selesai)->diffInMinutes(Carbon::parse($request->mulai));
            $downtime->duration = $duration;
        }
        $downtime->save();

        // Update total downtime
        $downtimeTotal = Downtime::sum('duration');
        Downtime::where('id', $downtime->id)->update(['downtimeTotal' => $downtimeTotal]);

        return redirect()->back()->with('success', 'Downtime updated successfully.');
    }

    public function toggleMachineStatus(Request $request)
    {
        $machineStatus = MachineStatus::latest()->first();

        if ($machineStatus) {
            if ($machineStatus->status) {
                $machineStatus->status = !$machineStatus->status;
                $machineStatus->stop_time = now();
                $machineStatus->save();
                // Jika mesin berhenti, catat waktu mulai downtime
                Downtime::create([
                    'downtimeid' => 'ID_' . time(), // Atur ID downtime sesuai kebutuhan
                    'downtimedesc' => 'Mesin berhenti',
                    'mulai' => now(),
                ]);
            } else {
                $machineStatus = new MachineStatus();
                $machineStatus->status = true;
                $machineStatus->start_time = now();
                $machineStatus->save();
                // Jika mesin mulai, catat waktu selesai downtime// Toggle the status
                $latestDowntime = Downtime::whereNull('selesai')->orderBy('created_at', 'desc')->first();
                if ($latestDowntime) {
                    $latestDowntime->selesai = now();
                    $latestDowntime->duration = $latestDowntime->selesai->diffInMinutes($latestDowntime->mulai);
                    $latestDowntime->save();
                }
            }

            return response()->json(['status' => $machineStatus->status ? 'on' : 'stop']);
        } else {
            // Handle case where there is no machine status
            $machineStatus = new MachineStatus();
            $machineStatus->status = true;
            $machineStatus->start_time = now();
            $machineStatus->save();
            return response()->json(['status' => 'on']);
        }
    }

    public function scheduleDowntime(Request $request)
    {
        $request->validate([
            'start_time' => 'required|date_format:Y-m-d\TH:i',
            'end_time' => 'nullable|date_format:Y-m-d\TH:i|after:start_time'
        ]);

        $scheduledDowntime = new ScheduledDowntime();
        $scheduledDowntime->start_time = Carbon::parse($request->start_time);
        if ($request->end_time) {
            $scheduledDowntime->end_time = Carbon::parse($request->end_time);
        }
        $scheduledDowntime->save();

        return redirect()->back()->with('success', 'Downtime Terjadwal berhasil ditambahkan');
    }

    public function storeItem(Request $request)
    {
        $request->validate([
            'nama_item' => 'required|string|max:255',
            'idealProduceTime' => 'required|integer'
        ]);

        $item = new Item();
        $item->nama_item = $request->nama_item;
        $item->idealProduceTime = $request->idealProduceTime;
        $item->save();

        return redirect()->back()->with('success', 'Item berhasil ditambahkan');
    }

    public function showItems()
    {
        $items = Item::all();
        return view('oee.items', compact('items'));
    }

    public function updateReject(Request $request)
    {
        $request->validate([
            'reject' => 'required|integer|min:0',
        ]);

        // Ambil atau buat OEE metric terbaru
        $latestOeeMetric = OeeMetric::latest()->first();
        if (!$latestOeeMetric) {
            $latestOeeMetric = new OeeMetric();
        }

        // Update jumlah reject
        $latestOeeMetric->reject = $request->reject;
        $latestOeeMetric->save();

        return redirect()->back()->with('success', 'Jumlah reject berhasil disimpan');
    }

    public function calculateOee() {
        $oeeMetrics = $this->calculateOeeMetrics();
        return response()->json(['success' => true, 'oeeMetrics' => $oeeMetrics]);
    }

    public function machineStartStore(Request $request)
    {
        $request->validate([
            'machine_start' => 'required|date_format:Y-m-d\TH:i',
        ]);

        MachineStartTime::create([
            'machine_start' => $request->machine_start,
        ]);

        return redirect()->back()->with('success', 'Machine start time berhasil disimpan');
    }
}