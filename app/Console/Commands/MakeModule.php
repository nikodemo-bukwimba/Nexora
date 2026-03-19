<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeModule extends Command
{
    protected $signature = 'nexora:module {name}';
    protected $description = 'Create a new Nexora module';

    public function handle()
    {
        $name = $this->argument('name');
        $basePath = base_path("modules/{$name}");

        $directories = [
            "Config",
            "Contracts/Repositories",
            "Contracts/Services",
            "Database/Migrations",
            "Database/Seeders",
            "Database/Factories",
            "Events",
            "Exceptions",
            "Http/Controllers/Api",
            "Http/Controllers/Web",
            "Http/Middleware",
            "Http/Requests",
            "Http/Resources",
            "Models",
            "Providers",
            "Repositories",
            "Routes",
            "Services",
            "Traits",
        ];

        foreach ($directories as $dir) {
            $path = "{$basePath}/{$dir}";
            File::makeDirectory($path, 0755, true, true);
        }

        $this->createServiceProvider($name);

        $this->info("Module {$name} created successfully.");
    }

    protected function createServiceProvider($name)
    {
        $providerPath = base_path("modules/{$name}/Providers/{$name}ServiceProvider.php");

        $content = "<?php

namespace Modules\\{$name}\\Providers;

use Illuminate\Support\ServiceProvider;

class {$name}ServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        //
    }
}
";

        File::put($providerPath, $content);
    }
}