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

        $idealProduceTime = Item::whereIn('tipe_barang', $nearestMachineEndTime->pluck('tipe_barang'))->sum('ideal_produce_time');

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

    public function calculateAvailability()
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
        $latestStatus = MachineStatus::where('start_time', '<=', $start_prod)
            ->orderBy('start_time', 'desc')
            ->first();
        $status = $latestStatus ? $latestStatus->status : false;
        if (!$status) {
            $startDown = $now->diffInMinutes($start_prod);
            $totalDowntime = $totalDowntime + $startDown;
        }

        // Calculate operatingTime
        $runtime = $now->diffInMinutes($start_prod); // Example runtime, this should be dynamically calculated
        $operatingTime = $runtime - $totalDowntime;

        // Calculate Availability
        $availability = ($operatingTime / $plannedTime) * 100;

        if ($availability < 0) {
            $availability = 0;
        } else if ($availability > 100) {
            $availability = 100;
        }

        // Calculate Performance
        $latestOeeMetric =  OeeMetric::where('timestamp', '>=', $start_prod)
            ->where('timestamp', '<=', $finish_prod)
            ->orderBy('timestamp', 'desc')
            ->first();
        $performance = $latestOeeMetric ? $latestOeeMetric->performance : 0;

        // Calculate Quality
        $quality =  $latestOeeMetric ? $latestOeeMetric->quality : 0;
        $rejects =  $latestOeeMetric ? $latestOeeMetric->reject : 0;

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

        return response()->json(['success' => true, 'oeeMetrics' => $oeeMetric]);
    }

    // private function calculateAvailability()
    // {
    //     $now = Carbon::now();
    //     $machineActive = MachineStartTime::where('start_prod', '<=', $now)
    //         ->where('finish_prod', '>=', $now)
    //         ->orderBy('start_prod', 'asc')
    //         ->first();

    //     $plannedTime = $machineActive->worktime; // Planned time in minutes
    //     $start_prod = Carbon::parse($machineActive->start_prod);
    //     $finish_prod = Carbon::parse($machineActive->finish_prod);
    //     $downtimes = Downtime::where('mulai', '>=', $start_prod);
    //     $totalDowntime = $downtimes->sum('duration');

    //     // Calculate operatingTime
    //     $runtime = $now->diffInMinutes($start_prod); // Example runtime, this should be dynamically calculated
    //     $operatingTime = $runtime - $totalDowntime;

    //     // Calculate Availability
    //     $availability = ($operatingTime / $plannedTime) * 100;

    //     // Calculate Performance
    //     $latestOeeMetric =  OeeMetric::where('timestamp', '>=', $start_prod)
    //     ->where('timestamp', '<=', $finish_prod);
    //     $performance = $latestOeeMetric ? $latestOeeMetric->performance : 0;

    //     // Calculate Quality
    //     $quality =  $latestOeeMetric ? $latestOeeMetric->quality : 0;
    //     $rejects =  $latestOeeMetric ? $latestOeeMetric->reject : 0;

    //     // Calculate OEE
    //     $oee = ($availability / 100) * ($performance / 100) * ($quality / 100) * 100;

    //     // Save OEE metrics to database
    //     $oeeMetric = new OeeMetric();
    //     $oeeMetric->availability = $availability;
    //     $oeeMetric->performance = $performance;
    //     $oeeMetric->quality = $quality;
    //     $oeeMetric->reject = $rejects;
    //     $oeeMetric->oee = $oee;
    //     $oeeMetric->timestamp = Carbon::now();
    //     $oeeMetric->save();

    //     return $oeeMetric;
    // }

    public function calculateOee() {
        $now = Carbon::now();
        $machineActive = MachineStartTime::where('start_prod', '<=', $now)
            ->where('finish_prod', '>=', $now)
            ->orderBy('start_prod', 'asc')
            ->first();

        $start_prod = Carbon::parse($machineActive->start_prod);
        $finish_prod = Carbon::parse($machineActive->finish_prod);
        $latestProduct = Production::where('timestamp_capture', '>=', $start_prod)
            ->where('timestamp_capture', '<', $now)
            ->orderBy('timestamp_capture', 'desc')
            ->first();
        $timeGap = $now->diffInMinutes($latestProduct->timestamp_capture);
        $downtimes = Downtime::where('mulai', '>=', $start_prod);
        $totalDowntime = $downtimes->sum('duration');
        if ($timeGap <= 1) {
            $runtime = $now->diffInMinutes($start_prod); // Example runtime, this should be dynamically calculated
            $operatingTime = $runtime - $totalDowntime;

            // Calculate Performance
            $productions = Production::where('timestamp_capture', '>=', $start_prod)
                ->where('timestamp_capture', '<=', $finish_prod);
            $totalProducedItems = $productions->count();
            $idealProduceTime = Item::whereIn('tipe_barang', $productions->pluck('tipe_barang'))->sum('ideal_produce_time');
            $performance = ($idealProduceTime * $totalProducedItems) / $operatingTime * 100;

            // Calculate Quality
            $outputMesin = $totalProducedItems; // Example value
            $latestOeeMetric =  OeeMetric::where('timestamp', '>=', $start_prod)
                ->where('timestamp', '<=', $now)
                ->orderBy('timestamp', 'desc')
                ->first();
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
            $availability = $latestOeeMetric ? $latestOeeMetric->availability : 0;

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

            return response()->json(['success' => true, 'oeeMetrics' => $oeeMetric]);
        }
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
        $latestStatus = MachineStatus::latest()->first();
        $status = $latestStatus ? $latestStatus->status : false;
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
