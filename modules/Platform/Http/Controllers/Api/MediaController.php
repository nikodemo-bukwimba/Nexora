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
     *   file  (required) — image file ≤ 10 MB
     *   type  (optional) — bucket hint: product | profile | org (default: product)
     *
     * Response 201:
     *   { "url": "https://...", "path": "media/product/<ulid>.jpg" }
     *
     * NOTE: mimes: validation is intentionally replaced with image validation
     * because Windows file paths from Flutter's file_picker can present with
     * uppercase extensions (.JPG, .PNG) or no extension at all, which causes
     * Laravel's mimes: rule to reject valid image files with a 422.
     * The `image` rule uses GD/Exif to verify the actual file content instead
     * of relying on the extension or Content-Type header.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'max:10240'],
            'type' => ['nullable', 'string', 'in:product,profile,org'],
        ]);

        $type = $request->input('type', 'product');
        $file = $request->file('file');
        $id   = (string) new Ulid();

        // Derive extension from the actual MIME type so uppercase or missing
        // extensions from Windows paths never produce a wrong filename.
        $mime      = $file->getMimeType() ?? 'image/jpeg';
        $extMap    = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        $ext  = $extMap[$mime] ?? strtolower($file->getClientOriginalExtension() ?: 'jpg');
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