<?php

namespace Modules\Platform\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Uid\Ulid;

class MediaController extends Controller
{
    /**
     * POST /api/v1/media/upload
     *
     * General-purpose file upload. Stores the file in the public disk
     * and returns a publicly accessible URL.
     *
     * Content-Type: multipart/form-data
     *
     * Fields:
     *   file  (required) — image file (jpg, jpeg, png, webp) ≤ 10 MB
     *   type  (optional) — bucket hint: product | profile | org (default: product)
     *
     * Response 201:
     *   { "url": "https://...", "path": "media/product/<ulid>.jpg" }
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp'],
            'type' => ['nullable', 'string', 'in:product,profile,org'],
        ]);

        $type = $request->input('type', 'product');
        $file = $request->file('file');
        $id   = (string) new Ulid();
        $ext  = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $dir  = "media/{$type}";
        $name = "{$id}.{$ext}";

        Storage::disk('public')->putFileAs($dir, $file, $name);

        $url = Storage::disk('public')->url("{$dir}/{$name}");

        return response()->json([
            'url'  => $url,
            'path' => "{$dir}/{$name}",
        ], 201);
    }
}