<?php

namespace Modules\Platform\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Platform\Models\Actor;

interface ActorRepositoryInterface
{
    public function findById(string $id): ?Actor;

    public function findByType(string $typeName): Collection;

    public function create(array $data): Actor;

    public function assignType(string $actorId, string $typeName, ?string $assignedBy = null): void;
}
