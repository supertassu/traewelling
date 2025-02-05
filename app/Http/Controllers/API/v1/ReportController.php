<?php

namespace App\Http\Controllers\API\v1;

use App\Enum\Report\ReportableSubject;
use App\Enum\Report\ReportReason;
use App\Enum\Report\ReportStatus;
use App\Models\Report;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Enum;

class ReportController extends Controller
{
    /**
     * @OA\Post(
     *      path="/report",
     *      summary="Report a Status, Event or User to the admins.",
     *      tags={"User", "Status", "Events"},
     *      security={{"passport": {}}, {"token": {}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"subject_type", "subject_id", "reason"},
     *              @OA\Property(property="subject_type", type="string", enum={"Event", "Status", "User"},
     *                                                    example="Status"),
     *              @OA\Property(property="subject_id", type="integer", example=1),
     *              @OA\Property(property="reason", type="string", enum={"inappropriate", "implausible", "spam",
     *                                              "illegal", "other"}, example="inappropriate"),
     *              @OA\Property(property="description", type="string", example="The status is inappropriate
     *                                                   because...", nullable=true),
     *          ),
     *      ),
     *      @OA\Response(response=200, description="The report was successfully created."),
     *      @OA\Response(response=401, description="The user is not authenticated."),
     *      @OA\Response(response=422, description="The given data was invalid."),
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse {
        $validated = $request->validate([
                                            'subject_type' => ['required', new Enum(ReportableSubject::class)],
                                            'subject_id'   => ['required', 'integer', 'min:1'],
                                            'reason'       => ['required', new Enum(ReportReason::class)],
                                            'description'  => ['nullable', 'string'],
                                        ]);

        $report = Report::create([
                                     'subject_type' => 'App\\Models\\' . $validated['subject_type'],
                                     'subject_id'   => $validated['subject_id'],
                                     'reason'       => $validated['reason'],
                                     'description'  => $validated['description'],
                                     'reporter_id'  => auth()->id(),
                                 ]);

        if (!App::runningUnitTests() && config('app.admin.notification.url') !== null) {
            Http::post(config('app.admin.notification.url'), [
                'chat_id'    => config('app.admin.notification.chat_id'),
                'text'       => "<b>🚨 New Report for " . $validated['subject_type'] . "</b>" . PHP_EOL
                                . "Reason: " . $validated['reason'] . PHP_EOL
                                . "Description: " . ($validated['description'] ?? 'None') . PHP_EOL
                                . "View Report: " . config('app.url') . "/admin/reports/" . $report->id . PHP_EOL
                ,
                'parse_mode' => 'HTML',
            ]);
        }

        return $this->sendResponse(
            data: 'Report created.',
            code: 201
        );
    }

    /**
     * Admin only - no public documentation.
     *
     * @param Request $request
     * @param int     $reportId
     *
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(Request $request, int $reportId): JsonResponse {
        $report = Report::findOrFail($reportId);
        $this->authorize('update', $report);

        $validated = $request->validate([
                                            'status'      => ['required', new Enum(ReportStatus::class)],
                                            'description' => ['nullable', 'string', 'max:255'],
                                        ]);

        $logger = activity()->causedBy(auth()->user())
                            ->performedOn($report);
        if ($validated['status'] !== $report->status->value) {
            $logger->withProperties([
                                        'attributes' => [
                                            'status' => $validated['status'],
                                        ],
                                        'old'        => [
                                            'status' => $report->status,
                                        ],
                                    ]);
        }
        $logger->log($validated['description'] ?? '');

        $report->update(['status' => $validated['status']]);

        return $this->sendResponse(
            data: 'Report updated.'
        );
    }
}
