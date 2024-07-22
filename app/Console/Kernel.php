<?php

namespace App\Console;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\Downtime;
use App\Models\OeeMetric;
use App\Models\Production;
use App\Models\MachineStatus;
use App\Models\MachineStartTime;
use App\Models\ScheduledDowntime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\OeeController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function() {
            $latestStatus = DB::table('machine_statuses')->latest()->first();
            $status = $latestStatus ? $latestStatus->status : 0;
            if ($status == 0) {
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
                $latestOeeMetric =  OeeMetric::where('timestamp', '>=', $start_prod)
                ->where('timestamp', '<=', $finish_prod);
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
            }
        })->everyMinute();

        $schedule->call(function() {
            $productions = DB::table('productions')->latest()->first();
            $idealProductionTime = DB::table('items')
                ->where('tipe_barang', $productions->tipe_barang)
                ->value('idealProduceTime');

            $lastRun = Cache::get('last_oee_metrics_run', now());

            if (now()->diffInMinutes($lastRun) >= $idealProductionTime) {
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
                Cache::put('last_oee_metrics_run', now());
            }
        })->everyMinute();
    }


    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}