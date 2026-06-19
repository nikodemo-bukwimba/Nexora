<?php

namespace Modules\Inventory\Console;

use Illuminate\Console\Command;
use Modules\Inventory\Models\StockReservation;
use Modules\Inventory\Contracts\Services\InventoryServiceInterface;

class ReleaseExpiredReservationsCommand extends Command
{
    protected $signature = 'inventory:release-expired-reservations';
    protected $description = 'Release stock reservations that have passed their expiry without being fulfilled or explicitly released.';

    public function __construct(protected InventoryServiceInterface $inventory)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $expired = StockReservation::where('status', 'active')
            ->where('expires_at', '<', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired reservations found.');
            return self::SUCCESS;
        }

        $released = 0;
        $failed   = 0;

        foreach ($expired as $reservation) {
            try {
                $this->inventory->releaseReservation($reservation->id);
                $released++;
            } catch (\Throwable $e) {
                $failed++;
                \Log::error("ReleaseExpiredReservations: failed to release {$reservation->id}: " . $e->getMessage());
            }
        }

        $this->info("Released {$released} expired reservation(s)." . ($failed ? " {$failed} failed — see logs." : ''));

        return self::SUCCESS;
    }
}