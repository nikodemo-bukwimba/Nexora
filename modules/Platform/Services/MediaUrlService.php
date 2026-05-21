namespace Modules\Platform\Services;

use Illuminate\Support\Facades\Storage;

class MediaUrlService
{
    public static function url(string $path): string
    {
        return Storage::disk('public')->url($path);
    }
}