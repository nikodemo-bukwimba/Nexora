<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,

    \Modules\Platform\Providers\PlatformServiceProvider::class,
    \Modules\Finance\Providers\FinanceServiceProvider::class,
    \Modules\Inventory\Providers\InventoryServiceProvider::class,
    \Modules\Commerce\Providers\CommerceServiceProvider::class,
    \Modules\Communications\Providers\CommunicationsServiceProvider::class,
    \Modules\Notifications\Providers\NotificationsServiceProvider::class,
    \Modules\Logistics\Providers\LogisticsServiceProvider::class,
    \Modules\PharmaMarketing\Providers\PharmaMarketingServiceProvider::class,

    \Modules\Delivery\Providers\DeliveryServiceProvider::class,
];