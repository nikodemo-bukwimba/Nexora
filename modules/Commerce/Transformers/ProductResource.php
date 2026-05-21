namespace Modules\Commerce\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Platform\Services\MediaUrlService;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,

            'media' => collect($this->media ?? [])->map(function ($item) {
                $item['url'] = MediaUrlService::url($item['path']);
                return $item;
            })->values(),
        ];
    }
}