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

        // Retrieve machine status
        $machineStatus = MachineStatus::latest()->first();
        $status = $machineStatus ? $machineStatus->status : null;

        // Ambil atau buat OEE metric terbaru
        $latestOeeMetrics = OeeMetric::latest()->first();
        if (!$latestOeeMetrics) {
            $latestOeeMetrics = new OeeMetric();
            $latestOeeMetrics->availability = 100;
            $latestOeeMetrics->performance = 100;
            $latestOeeMetrics->quality = 100;
            $latestOeeMetrics->oee = 100;
            $latestOeeMetrics->reject = 0;
        }
        $latestReject = $latestOeeMetrics->reject;

        $now = Carbon::now();
        $nearestMachineStartTime = MachineStartTime::where('machine_start', '>=', $now)
            ->orderBy('machine_start', 'asc')
            ->first();

        $nearestMachineEndTime = MachineStartTime::where('machine_end', '>=', $now)
            ->orderBy('machine_start', 'asc')
            ->first();

        $nearestDowntimeSchedule = ScheduledDowntime::where('start_time', '>=', $now)
            ->orderBy('start_time', 'asc')
            ->first();

        return view('oee.index', compact('productions', 'latestOeeMetrics', 'status', 'latestReject', 'nearestMachineStartTime', 'nearestMachineEndTime', 'nearestDowntimeSchedule'));
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
        $machineActive = MachineStartTime::where('machine_start', '<=', $now)
            ->where('machine_end', '>=', $now)
            ->orderBy('machine_start', 'asc')
            ->first();

        $plannedTime = $machineActive->planned_time; // Planned time in minutes
        $machineStart = Carbon::parse($machineActive->machine_start);
        $machineEnd = Carbon::parse($machineActive->machine_end);
        $downtimes = Downtime::where('mulai', '>=', $machineStart);
        $totalDowntime = $downtimes->sum('duration');

        // Calculate operatingTime
        $runtime = $now->diffInMinutes($machineStart); // Example runtime, this should be dynamically calculated
        $operatingTime = $runtime - $totalDowntime;

        // Calculate Availability
        $availability = ($operatingTime / $plannedTime) * 100;

        // Calculate Performance
        $productions = Production::where('timestamp_capture', '>=', $machineStart)
            ->where('timestamp_capture', '<=', $machineEnd);
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
            'machine_end' => 'required|date_format:Y-m-d\TH:i|after:machine_start',
            'planned_time' => 'required|integer|min:0'
        ]);

        MachineStartTime::create([
            'machine_start' => $request->machine_start,
            'machine_end' => $request->machine_end,
            'planned_time' => $request->planned_time
        ]);

        return redirect()->back()->with('success', 'Shift time berhasil disimpan');
    }
}