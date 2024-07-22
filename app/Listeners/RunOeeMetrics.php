<?php

namespace App\Listeners;

use App\Http\Controllers\OeeController;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RunOeeMetrics
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        // Panggil fungsi calculateOeeMetrics
        app(OeeController::class)->calculateOeeMetrics();
    }
}
