<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Carbon\Carbon;
use App\Models\ScheduledDowntime;
use App\Models\MachineStatus;
use App\Models\Downtime;

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
        $schedule->call(function () {
            $now = Carbon::now();

            $activeDowntime = ScheduledDowntime::where('is_active', true)->first();
            if ($activeDowntime) {
                if ($activeDowntime->end_time && $now->greaterThanOrEqualTo($activeDowntime->end_time)) {
                    // Akhiri downtime terjadwal
                    $machineStatus = MachineStatus::latest()->first();
                    if ($machineStatus && !$machineStatus->status) {
                        $machineStatus->status = true;
                        $machineStatus->start_time = now();
                        $machineStatus->save();

                        // Nonaktifkan downtime terjadwal
                        $activeDowntime->is_active = false;
                        $activeDowntime->save();

                        $latestDowntime = Downtime::whereNull('selesai')->orderBy('created_at', 'desc')->first();
                        if ($latestDowntime) {
                            $latestDowntime->selesai = now();
                            $latestDowntime->duration = $latestDowntime->selesai->diffInMinutes($latestDowntime->mulai);
                            $latestDowntime->save();
                        }
                    }
                }
            } else {
                $scheduledDowntime = ScheduledDowntime::where('start_time', '>=', $now)->where(function ($query) use ($now) {
                    $query->whereNull('end_time')
                        ->orWhere('end_time', '>=', $now);
                })->first();

                if ($scheduledDowntime) {
                    // Mulai downtime terjadwal
                    $machineStatus = MachineStatus::latest()->first();
                    if ($machineStatus && $machineStatus->status) {
                        $machineStatus->status = false;
                        $machineStatus->stop_time = now();
                        $machineStatus->save();

                        // Aktifkan downtime terjadwal
                        $scheduledDowntime->is_active = true;
                        $scheduledDowntime->save();

                        // Jika mesin berhenti, catat waktu mulai downtime
                        Downtime::create([
                            'downtimeid' => 'ID_' . time(), // Atur ID downtime sesuai kebutuhan
                            'downtimedesc' => 'Mesin berhenti',
                            'mulai' => now(),
                        ]);
                    }
                }
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