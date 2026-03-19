<?php

namespace Modules\Platform\Repositories;

use Illuminate\Support\Collection;
use Modules\Platform\Contracts\Repositories\ActorRepositoryInterface;
use Modules\Platform\Models\Actor;
use Modules\Platform\Models\ActorType;
use Modules\Platform\Models\ActorTypeAssignment;

class ActorRepository implements ActorRepositoryInterface
{
    public function findById(string $id): ?Actor
    {
        return Actor::find($id);
    }

    public function findByType(string $typeName): Collection
    {
        return Actor::whereHas('types', fn($q) => $q->where('name', $typeName))->get();
    }

    public function create(array $data): Actor
    {
        return Actor::create($data);
    }

    public function assignType(string $actorId, string $typeName, ?string $assignedBy = null): void
    {
        $type = ActorType::where('name', $typeName)->firstOrFail();

        ActorTypeAssignment::insertOrIgnore([
            'actor_id'      => $actorId,
            'actor_type_id' => $type->id,
            'assigned_at'   => now(),
            'assigned_by'   => $assignedBy,
        ]);
    }
}
