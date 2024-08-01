<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\OeeData;
use App\Models\Downtime;
use App\Models\OeeMetric;
use App\Models\Production;
use Illuminate\Http\Request;
use App\Models\MachineStatus;
use App\Models\MachineStartTime;
use App\Models\ScheduledDowntime;
use Illuminate\Support\Facades\Cache;

class OeeController extends Controller
{
    public function index() {

        // $data = OeeData::orderBy('id', 'asc')->get();

        // return view('oee.index', compact('data'));

        // Retrieve machine status
        $machineStatus = MachineStatus::latest()->first();
        $status = $machineStatus ? $machineStatus->status : null;

        // Ambil atau buat OEE metric terbaru
        $latestOeeMetrics = OeeMetric::latest()->first();
        if (!$latestOeeMetrics) {
            $latestOeeMetrics = new OeeMetric();
            $latestOeeMetrics->availability = 0;
            $latestOeeMetrics->performance = 0;
            $latestOeeMetrics->quality = 0;
            $latestOeeMetrics->oee = 0;
            $latestOeeMetrics->reject = 0;
        }
        $latestReject = $latestOeeMetrics->reject;

        $now = Carbon::now();
        $nearestMachineStartTime = MachineStartTime::where('start_prod', '>=', $now)
            ->orderBy('start_prod', 'asc')
            ->first();

        $nearestMachineEndTime = MachineStartTime::where('finish_prod', '>=', $now)
            ->orderBy('start_prod', 'asc')
            ->first();

        $idealProduceTime = 0;

        if ($nearestMachineEndTime) {
            $idealProduceTime = Item::whereIn('tipe_barang', $nearestMachineEndTime->pluck('tipe_barang'))->sum('ideal_produce_time');
        }

        $latestProduction = Production::latest()->first();

        return view(
            'oee.index',
            compact(
                'latestProduction',
                'latestOeeMetrics',
                'status',
                'latestReject',
                'nearestMachineStartTime',
                'nearestMachineEndTime',
                'idealProduceTime'
            ));
    }

    public function fetchOeeMetrics() {
        $oeeMetric = OeeMetric::latest()->first();
        if (!$oeeMetric) {
            $oeeMetric = new OeeMetric();
            $oeeMetric->availability = 100;
            $oeeMetric->performance = 100;
            $oeeMetric->quality = 100;
            $oeeMetric->oee = 100;
            $oeeMetric->reject = 0;
        }

        return $oeeMetric;
    }

    public function getProductions() {
        $productions = Production::paginate(10);

            return view('oee.productions', compact('productions'));
    }

    public function getMetrics() {
        $metrics = OeeMetric::paginate(10);

            return view('oee.metrics', compact('metrics'));
    }

    private function calculateOeeMetrics()
    {
        $now = Carbon::now();
        $machineActive = MachineStartTime::where('start_prod', '<=', $now)
            ->where('finish_prod', '>=', $now)
            ->orderBy('start_prod', 'asc')
            ->first();

        $plannedTime = $machineActive->worktime; // Planned time in minutes
        $start_prod = Carbon::parse($machineActive->start_prod);
        $finish_prod = Carbon::parse($machineActive->finish_prod);
        $downtimes = Downtime::where('mulai', '>=', $start_prod);
        $totalDowntime = $downtimes->sum('duration');

        // Calculate operatingTime
        $runtime = $now->diffInMinutes($start_prod); // Example runtime, this should be dynamically calculated
        $operatingTime = $runtime - $totalDowntime;

        // Calculate Availability
        $availability = ($operatingTime / $plannedTime) * 100;

        // Calculate Performance
        $productions = Production::where('timestamp_capture', '>=', $start_prod)
            ->where('timestamp_capture', '<=', $finish_prod);
        $totalProducedItems = $productions->count();
        $idealProduceTime = Item::whereIn('tipe_barang', $productions->pluck('tipe_barang'))->sum('idealProduceTime');
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

    public function calculateOee() {
        $now = Carbon::now();
        $machineActive = MachineStartTime::where('start_prod', '<=', $now)
            ->where('finish_prod', '>=', $now)
            ->orderBy('start_prod', 'asc')
            ->first();

        $plannedTime = $machineActive->worktime;
        $start_prod = Carbon::parse($machineActive->start_prod);
        $finish_prod = Carbon::parse($machineActive->finish_prod);
        $latestOeeMetric =  OeeMetric::where('timestamp', '>=', $start_prod)
            ->where('timestamp', '<=', $now)
            ->orderBy('timestamp', 'desc')
            ->first();
        $downtime = $latestOeeMetric ? $latestOeeMetric->downtime : 0;
        $latestStatus = MachineStatus::latest()->first();
        $status = $latestStatus ? $latestStatus->status : false;
        if (!$status) {
            $downtime++;
        }
        $runtimeSecs = $now->diffInSeconds($start_prod); // Example runtime, this should be dynamically calculated
        $runtimeMins = $runtimeSecs/60; // Example runtime, this should be dynamically calculated
        $runtime = $now->diffInMinutes($start_prod); // Example runtime, this should be dynamically calculated
        $operatingTimeSecs = ($runtimeSecs - $downtime)/60;
        $operatingTime = $runtime - ($downtime/60);
        $downtimeMins = $downtime/60;
        $rejects = $latestOeeMetric ? $latestOeeMetric->reject : 0;
        $latestProduct = Production::where('timestamp_capture', '>=', $start_prod)
            ->where('timestamp_capture', '<', $now)
            ->orderBy('timestamp_capture', 'desc')
            ->first();
        $productions = Production::where('timestamp_capture', '>=', $start_prod)
            ->where('timestamp_capture', '<=', $finish_prod)
            ->get();
        $totalProducedItems = $productions->count();
        $idealProduceTime = 0;
        $performance = $latestOeeMetric ? $latestOeeMetric->performance : 0;
        if ($latestProduct) {
            $timeGap = $now->diffInMinutes($latestProduct->timestamp_capture);
            $idealProduceTime = Item::whereIn('tipe_barang', $productions->pluck('tipe_barang'))->sum('ideal_produce_time');
            if (($runtime % $idealProduceTime) == 0) {
                $performance = ($idealProduceTime * $totalProducedItems) / $operatingTime * 100;
            } else if ($timeGap <= 1) {
                $performance = ($idealProduceTime * $totalProducedItems) / $operatingTime * 100;
            }
        }
        // Calculate Quality
        $outputMesin = $totalProducedItems;
        $tipeBarang = $latestProduct ? $latestProduct->tipe_barang : '-';
        $outputTime = ($outputMesin*$idealProduceTime)/60;
        $cycleCount = 0;
        if ($idealProduceTime != 0) {
            $cycleCount = $runtime/$idealProduceTime;
        }
        $defectTime = 0;
        if ($rejects != 0 && $idealProduceTime != 0) {
            $defectTime = ($rejects*$idealProduceTime)/60;
        }

        // Check if $outputMesin is greater than zero to avoid DivisionByZeroError
        if ($outputMesin > 0) {
            $outputStandar = $outputMesin - $rejects;
            $quality = ($outputStandar / $outputMesin) * 100;
        } else {
            // Handle the case where $outputMesin is zero (or less than or equal to zero)
            // For example, set quality to zero or handle it based on your business logic.
            $quality = 0; // or any default value or handling logic
        }
        $availability = $latestOeeMetric ? $latestOeeMetric->availability : 0;
        $availability = ($operatingTime / $plannedTime) * 100;

        // Calculate OEE
        $oee = ($availability / 100) * ($performance / 100) * ($quality / 100) * 100;

        if ($availability < 0) {
            $availability = 0;
        } else if ($availability > 100) {
            $availability = 100;
        }
        if ($performance < 0) {
            $performance = 0;
        } else if ($performance > 100) {
            $performance = 100;
        }
        if ($quality < 0) {
            $quality = 0;
        } else if ($quality > 100) {
            $quality = 100;
        }
        if ($oee < 0) {
            $oee = 0;
        } else if ($oee > 100) {
            $oee = 100;
        }

        $machineStatus = MachineStatus::latest()->first();
        $status = $machineStatus ? $machineStatus->status : false;
        $now = Carbon::now();
        $latestDowntime = Downtime::orderBy('id', 'desc')
            ->first();
        if ($latestDowntime) {
            $mulai = Carbon::parse($latestDowntime->mulai);
            $selesai = Carbon::parse($latestDowntime->selesai);
            if ($now >= $selesai) {
                if (!$status) {
                    $machineStatus = new MachineStatus();
                    $machineStatus->status = true;
                    $machineStatus->start_time = now();
                    $machineStatus->save();
                    $status = true;
                }
            } else if ($mulai <= $now) {
                if ($status) {
                    $machineStatus->status = false;
                    $machineStatus->stop_time = now();
                    $machineStatus->save();
                    $status = false;
                }
            }
        }

        // Save OEE metrics to database
        $oeeMetric = new OeeMetric();
        $oeeMetric->availability = $availability;
        $oeeMetric->performance = $performance;
        $oeeMetric->quality = $quality;
        $oeeMetric->reject = $rejects;
        $oeeMetric->oee = $oee;
        $oeeMetric->runtime = $runtimeMins;
        $oeeMetric->downtime = $downtime;
        $oeeMetric->operating_time = $operatingTimeSecs;
        $oeeMetric->timestamp = Carbon::now();
        $oeeMetric->save();

        $dandori = Downtime::where('downtimedesc', '=', 'Dandori')
            ->where('mulai', '>=', $start_prod)
            ->where('selesai', '<=', $finish_prod)
            ->sum('duration');
        $others = Downtime::where('downtimedesc', '=', 'Others')
            ->where('mulai', '>=', $start_prod)
            ->where('selesai', '<=', $finish_prod)
            ->sum('duration');
        $tool = Downtime::where('downtimedesc', '=', 'Tool')
            ->where('mulai', '>=', $start_prod)
            ->where('selesai', '<=', $finish_prod)
            ->sum('duration');
        $start_up = Downtime::where('downtimedesc', '=', 'Startup')
            ->where('mulai', '>=', $start_prod)
            ->where('selesai', '<=', $finish_prod)
            ->sum('duration');
        $breakdown = Downtime::where('downtimedesc', '=', 'Breakdown')
            ->where('mulai', '>=', $start_prod)
            ->where('selesai', '<=', $finish_prod)
            ->sum('duration');

        return response()->json([
            'success' => true,
            'oeeMetrics' => $oeeMetric,
            'tipeBarang' => $tipeBarang,
            'totalItems' => $totalProducedItems,
            'cycleTime' => $idealProduceTime,
            'outputTime' => $outputTime,
            'cycleCount' => $cycleCount,
            'defectTime' => $defectTime,
            'dandori' => $dandori,
            'others' => $others,
            'tool' => $tool,
            'start_up' => $start_up,
            'breakdown' => $breakdown,
            'productions' => $productions,
            'start_prod' => $start_prod,
            'status' => $status,
            'latestDowntime' => $latestDowntime
        ]);
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

    public function getMachineStatus() {
        $machineStatus = MachineStatus::latest()->first();
        $status = $machineStatus->status;
        $now = Carbon::now();
        $latestDowntime = Downtime::where('mulai', '<=', $now)
            ->where('selesai')
            ->orderBy('mulai', 'asc')
            ->first();
        if ($latestDowntime) {
            $mulai = Carbon::parse($latestDowntime->mulai);
            $selesai = Carbon::parse($latestDowntime->selesai);
            if ($mulai <= $now) {
                if ($status) {
                    $machineStatus->status = !$machineStatus->status;
                    $machineStatus->stop_time = now();
                    $machineStatus->save();
                }
            } else if ($selesai >= $now) {
                if (!$status) {
                    $machineStatus = new MachineStatus();
                    $machineStatus->status = true;
                    $machineStatus->start_time = now();
                    $machineStatus->save();
                }
            }
        }
        $response = $status ? 'on' : 'stop';
        return response()->json(['status' => $response]);
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
        $item->tipe_barang = $request->tipe_barang;
        $item->ideal_produce_time = $request->idealProduceTime;
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

    // public function calculateOee() {
    //     $oeeMetrics = $this->calculateOeeMetrics();
    //     return response()->json(['success' => true, 'oeeMetrics' => $oeeMetrics]);
    // }

    // public function start_prodStore(Request $request)
    // {
    //     $request->validate([
    //         'start_prod' => 'required|date_format:Y-m-d\TH:i',
    //         'finish_prod' => 'required|date_format:Y-m-d\TH:i|after:start_prod',
    //         'planned_time' => 'required|integer|min:0'
    //     ]);

    //     MachineStartTime::create([
    //         'start_prod' => $request->start_prod,
    //         'finish_prod' => $request->finish_prod,
    //         'planned_time' => $request->planned_time
    //     ]);

    //     return redirect()->back()->with('success', 'Shift time berhasil disimpan');
    // }
}