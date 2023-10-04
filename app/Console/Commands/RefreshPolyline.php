<?php

namespace App\Console\Commands;

use App\Exceptions\DistanceDeviationException;
use App\Http\Controllers\Backend\BrouterController;
use App\Http\Controllers\Backend\Transport\TrainCheckinController;
use App\Models\HafasTrip;
use App\Models\Status;
use Illuminate\Console\Command;

class RefreshPolyline extends Command
{
    protected $signature   = 'trwl:refreshPolyline {start} {end}';
    protected $description = 'Rerun the brouter polyline calculation for all trips in a certain date range';

    public function handle(): void {
        $start_date      = $this->arguments()['start'];
        $end_date      = $this->arguments()['end'];
        $trips = HafasTrip::where('created_at', '<=', $start_date)->andWhere("created_at", '>=', $end_date);

        $this->info(sprintf('Found %d hafas trips between %s and %s', count($trips), $start_date, $end_date));
        $this->newLine(3);
        $this->ask("Enter to continue or ^C to cancel.");

        $this->withProgressBar($trips, function (HafasTrip $trip) {
            BrouterController::reroutePolyline($trip);
        });
    }
}
