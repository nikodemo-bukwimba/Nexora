<?php

namespace Modules\PharmaMarketing\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Platform\Contracts\Services\OrgScopeResolverInterface;
use Modules\PharmaMarketing\Models\FieldVisit;
use Modules\PharmaMarketing\Models\VisitAttachment;
use Modules\PharmaMarketing\Models\VisitProduct;
use Modules\PharmaMarketing\Models\WeeklyPlanItem;

class FieldVisitService
{
    public function __construct(
        protected OrgScopeResolverInterface $scope
    ) {}

    public function checkIn(string $orgId, string $officerActorId, string $customerId, array $data): FieldVisit
    {
        return FieldVisit::create([
            'org_id'                        => $orgId,
            'customer_id'                   => $customerId,
            'officer_actor_id'              => $officerActorId,
            'weekly_plan_item_id'           => $data['weekly_plan_item_id'] ?? null,
            'visit_type'                    => $data['visit_type'] ?? 'routine',
            'status'                        => 'in_progress',
            'check_in_at'                   => now(),
            'check_in_latitude'             => $data['latitude'] ?? null,
            'check_in_longitude'            => $data['longitude'] ?? null,
            'check_in_gps_accuracy_meters'  => $data['gps_accuracy'] ?? null,
            'objective'                     => $data['objective'] ?? null,
            'contact_person_id'             => $data['contact_person_id'] ?? null,
            'contact_person_name'           => $data['contact_person_name'] ?? null,
        ]);
    }

    public function checkOut(string $visitId, string $officerActorId, array $data): FieldVisit
    {
        return DB::connection('pharma_marketing')->transaction(function () use ($visitId, $officerActorId, $data) {
            $visit = FieldVisit::where('id', $visitId)
                ->where('officer_actor_id', $officerActorId)
                ->where('status', 'in_progress')
                ->firstOrFail();

            $checkOut = now();
            $duration = (int) $visit->check_in_at->diffInMinutes($checkOut);

            $visit->update([
                'status'                => 'completed',
                'check_out_at'          => $checkOut,
                'check_out_latitude'    => $data['latitude'] ?? null,
                'check_out_longitude'   => $data['longitude'] ?? null,
                'duration_minutes'      => $duration,
                'discussion_summary'    => $data['discussion_summary'] ?? null,
                'outcome'               => $data['outcome'] ?? null,
                'outcome_status'        => $data['outcome_status'] ?? null,
                'follow_up_notes'       => $data['follow_up_notes'] ?? null,
                'follow_up_date'        => $data['follow_up_date'] ?? null,
                'notes'                 => $data['notes'] ?? null,
            ]);

            foreach ($data['products'] ?? [] as $productData) {
                VisitProduct::create([
                    'visit_id'          => $visitId,
                    'product_id'        => $productData['product_id'],
                    'product_name'      => $productData['product_name'] ?? 'Unknown',
                    'action'            => $productData['action'] ?? 'promoted',
                    'samples_given'     => $productData['samples_given'] ?? 0,
                    'customer_feedback' => $productData['customer_feedback'] ?? null,
                ]);
            }

            if ($visit->weekly_plan_item_id) {
                WeeklyPlanItem::where('id', $visit->weekly_plan_item_id)
                    ->update(['status' => 'completed', 'visit_id' => $visitId]);
            }

            return $visit->fresh(['attachments', 'products', 'customer']);
        });
    }

    public function get(string $id): FieldVisit
    {
        return FieldVisit::with(['customer', 'attachments', 'products', 'planItem'])->findOrFail($id);
    }

    /**
     * List visits with org-tree awareness.
     *
     * Root admin   → sees visits from ALL branches in the tree
     * Branch user  → sees visits from their branch only
     *
     * Root admin can filter by branch:
     *   $filters['branch_id'] = '01KMQ1...'
     */
    public function list(string $orgId, array $filters, int $perPage): LengthAwarePaginator
    {
        $orgIds = $this->scope->scopeIds($orgId, $filters['branch_id'] ?? null);

        return FieldVisit::whereIn('org_id', $orgIds)
            ->when(isset($filters['officer_id']),  fn($q) => $q->where('officer_actor_id', $filters['officer_id']))
            ->when(isset($filters['customer_id']), fn($q) => $q->where('customer_id', $filters['customer_id']))
            ->when(isset($filters['status']),      fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['date']),        fn($q) => $q->whereDate('check_in_at', $filters['date']))
            ->when(isset($filters['from']),        fn($q) => $q->where('check_in_at', '>=', $filters['from']))
            ->when(isset($filters['to']),          fn($q) => $q->where('check_in_at', '<=', $filters['to']))
            ->with(['customer'])
            ->orderBy('check_in_at', 'desc')
            ->paginate($perPage);
    }

    public function uploadAttachment(string $visitId, string $actorId, \Illuminate\Http\UploadedFile $file, array $data = []): VisitAttachment
    {
        $disk     = config('pharma_marketing.media_disk', 'public');
        $path     = $file->store("visits/{$visitId}", $disk);
        $url      = Storage::disk($disk)->url($path);
        $mimeType = $file->getMimeType();
        $type     = str_starts_with($mimeType, 'image/') ? 'photo' : 'document';

        return VisitAttachment::create([
            'visit_id'        => $visitId,
            'uploaded_by'     => $actorId,
            'type'            => $type,
            'file_name'       => $file->getClientOriginalName(),
            'file_url'        => $url,
            'mime_type'       => $mimeType,
            'file_size_bytes' => $file->getSize(),
            'caption'         => $data['caption'] ?? null,
            'latitude'        => $data['latitude'] ?? null,
            'longitude'       => $data['longitude'] ?? null,
        ]);
    }
}