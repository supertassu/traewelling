<?php

namespace App\Http\Controllers\API\v1;

use App\Enum\HafasTravelType;
use App\Http\Controllers\Backend\Transport\TripController as TripBackend;
use App\Http\Resources\HafasTripResource;
use App\Models\HafasTrip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;

class TripController extends Controller
{

    /**
     * Undocumented beta endpoint - only specific users have access
     *
     * @param Request $request
     *
     * @return HafasTripResource
     */
    public function createTrip(Request $request) {
        if (auth()->user()->role < 10) {
            abort(403, 'this endpoint is currently only available for beta users');
        }

        $validated = $request->validate([
                                            'category'                  => ['required', new Enum(HafasTravelType::class)],
                                            'lineName'                  => ['required'],
                                            'journey_number'            => ['nullable', 'numeric', 'min:1'],
                                            'operator_id'               => ['nullable', 'numeric', 'exists:hafas_operators,id'],
                                            //TODO: add stopovers, move origin/destination to stopovers
                                            'originId'                  => ['required', 'exists:train_stations,ibnr'],
                                            'originDeparturePlanned'    => ['required', 'date'],
                                            'destinationId'             => ['required', 'exists:train_stations,ibnr'],
                                            'destinationArrivalPlanned' => ['required', 'date'],
                                        ]);

        DB::beginTransaction();
        $trip = HafasTrip::create([
                                      'trip_id'        => TripBackend::generateUniqueTripId(),
                                      'category'       => $validated['category'],
                                      'number'         => $validated['lineName'],
                                      'linename'       => $validated['lineName'],
                                      'journey_number' => $validated['journey_number'],
                                      'operator_id'    => $validated['operator_id'],
                                      'origin'         => $validated['originId'],
                                      'destination'    => $validated['destinationId'],
                                      'departure'      => $validated['originDeparturePlanned'],
                                      'arrival'        => $validated['destinationArrivalPlanned'],
                                  ]);

        //TODO: stopovers here
        DB::commit();

        return new HafasTripResource($trip);
    }
}
