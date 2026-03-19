<?php

namespace Modules\Platform\Traits;

use Symfony\Component\Uid\Ulid;

trait HasUlid
{
    public static function bootHasUlid(): void
    {
        static::creating(function ($model) {
            $keyName = $model->getKeyName();

            // Skip composite-PK models (primaryKey = null)
            // and bigint/serial models ($incrementing = true)
            if (empty($keyName) || $model->getIncrementing()) {
                return;
            }

            if (empty($model->{$keyName})) {
                $model->{$keyName} = (string) new Ulid();
            }
        });
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}
