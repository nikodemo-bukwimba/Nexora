<?php
// === FILE: Modules/Delivery/Models/Delivery.php
 
namespace Modules\Delivery\Models;
 
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
 
class Delivery extends Model
{
    use HasUlids, SoftDeletes;
 
    protected $connection = 'delivery';
    protected $table      = 'deliveries';
 
    protected $fillable = [
        'org_id', 'order_id', 'tracking_number',
        'transporter_name', 'transporter_phone', 'car_registration',
        'driver_latitude', 'driver_longitude', 'location_updated_at',
        'cargo_fare', 'fare_is_paid',
        'parcel_image_path', 'waybill_image_path',
        'sender_name', 'sender_location', 'sender_phone',
        'receiver_name', 'receiver_location', 'receiver_phone',
        'status', 'notes', 'estimated_arrival',
        'delivered_at', 'cancelled_at',
    ];
 
    protected function casts(): array
    {
        return [
            'cargo_fare'           => 'decimal:2',
            'fare_is_paid'         => 'boolean',
            'driver_latitude'      => 'decimal:7',
            'driver_longitude'     => 'decimal:7',
            'location_updated_at'  => 'datetime',
            'estimated_arrival'    => 'datetime',
            'delivered_at'         => 'datetime',
            'cancelled_at'         => 'datetime',
        ];
    }
 
    // ── Auto-generate tracking number on create ─────────────────
 
    protected static function booted(): void
    {
        static::creating(function (Delivery $delivery) {
            if (empty($delivery->tracking_number)) {
                $delivery->tracking_number = static::generateTrackingNumber();
            }
        });
    }
 
    /**
     * Generate a unique human-readable tracking number.
     * Format: TRK-{YEAR}-{4 random uppercase alphanumeric chars}
     * e.g.  TRK-2026-A3F9
     */
    public static function generateTrackingNumber(): string
    {
        $year = now()->year;
        do {
            $suffix = strtoupper(Str::random(4));
            $number = "TRK-{$year}-{$suffix}";
        } while (static::where('tracking_number', $number)->exists());
 
        return $number;
    }
 
    // ── Scopes ──────────────────────────────────────────────────
 
    public function scopeForOrg($query, string $orgId)
    {
        return $query->where('org_id', $orgId);
    }
 
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'in_transit']);
    }
 
    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }
 
    // ── Helpers ─────────────────────────────────────────────────
 
    public function isTerminal(): bool
    {
        return in_array($this->status, ['delivered', 'cancelled', 'failed']);
    }
 
    public function hasLiveLocation(): bool
    {
        return $this->driver_latitude  !== null
            && $this->driver_longitude !== null;
    }
 
    public function isLocationFresh(): bool
    {
        if (! $this->location_updated_at) return false;
        return now()->diffInMinutes($this->location_updated_at) < 5;
    }
 
    /**
     * Build the public tracking payload.
     * This is what GET /track/{number} returns — safe for unauthenticated eyes.
     */
    public function toPublicTracking(): array
    {
        return [
            'tracking_number'    => $this->tracking_number,
            'delivery'           => [
                'id'               => $this->id,
                'status'           => $this->status,
                'car_registration' => $this->car_registration,
                'transporter_phone'=> $this->transporter_phone,
                'sender_location'  => $this->sender_location,
                'receiver_name'    => $this->receiver_name,
                'receiver_location'=> $this->receiver_location,
                // Only expose GPS when in transit
                'driver_latitude'  => $this->status === 'in_transit'
                    ? $this->driver_latitude  : null,
                'driver_longitude' => $this->status === 'in_transit'
                    ? $this->driver_longitude : null,
                'location_updated_at' => $this->status === 'in_transit'
                    ? $this->location_updated_at?->toISOString() : null,
            ],
            'estimated_arrival'  => $this->estimated_arrival?->toISOString(),
            'timeline'           => $this->buildTimeline(),
        ];
    }
 
    private function buildTimeline(): array
    {
        $events = [];
 
        $events[] = [
            'label'       => 'Imeandikishwa',
            'status'      => 'pending',
            'occurred_at' => $this->created_at->toISOString(),
        ];
 
        if (in_array($this->status, ['in_transit', 'delivered', 'failed'])) {
            $events[] = [
                'label'       => 'Imepakia — Safarini',
                'status'      => 'in_transit',
                'occurred_at' => $this->updated_at->toISOString(),
            ];
        }
 
        if ($this->status === 'delivered' && $this->delivered_at) {
            $events[] = [
                'label'       => 'Imefikia mpokeaji',
                'status'      => 'delivered',
                'occurred_at' => $this->delivered_at->toISOString(),
            ];
        }
 
        if ($this->status === 'cancelled' && $this->cancelled_at) {
            $events[] = [
                'label'       => 'Imefutwa',
                'status'      => 'cancelled',
                'occurred_at' => $this->cancelled_at->toISOString(),
            ];
        }
 
        if ($this->status === 'failed') {
            $events[] = [
                'label'       => 'Imeshindwa',
                'status'      => 'failed',
                'occurred_at' => $this->updated_at->toISOString(),
            ];
        }
 
        return $events;
    }
}