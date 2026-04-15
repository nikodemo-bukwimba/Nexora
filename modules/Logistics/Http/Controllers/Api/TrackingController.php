<?php

namespace Modules\Logistics\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Logistics\Services\OrderTrackingService;

class TrackingController extends Controller
{
    public function __construct(protected OrderTrackingService $tracking) {}

    /** GET /api/v1/logistics/track/{orderId} */
    public function trackOrder(string $orderId): JsonResponse
    {
        return response()->json($this->tracking->getTrackingForOrder($orderId));
    }
}
