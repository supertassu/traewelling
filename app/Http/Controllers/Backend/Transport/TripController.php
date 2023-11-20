<?php

namespace App\Http\Controllers\Backend\Transport;

use App\Http\Controllers\Controller;
use App\Models\HafasTrip;
use App\Models\Status;
use App\Models\TrainStation;
use App\Models\TrainStopover;
use Illuminate\Support\Str;

abstract class TripController extends Controller
{

    public static function generateUniqueTripId(): string {
        $tripId = Str::uuid();
        while (HafasTrip::where('trip_id', $tripId)->exists()) {
            return self::generateUniqueTripId();
        }
        return $tripId;
    }
}
