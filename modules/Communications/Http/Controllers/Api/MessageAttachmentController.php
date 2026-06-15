<?php

namespace Modules\Communications\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Communications\Models\MessageAttachment;

class MessageAttachmentController extends Controller
{
    /**
     * POST /api/v1/communications/attachments/upload
     *
     * Accepts multipart/form-data with a single `file` field.
     * Returns attachment metadata including the public URL.
     * The attachment is unclaimed (message_id = null) until the
     * send-message call claims it via attachment_ids[].
     */
    public function upload(Request $request): JsonResponse
    {
        Log::info('AttachmentUpload: request received', [
            'has_file'     => $request->hasFile('file'),
            'content_type' => $request->header('Content-Type'),
            'all_keys'     => array_keys($request->allFiles()),
        ]);

        $request->validate([
            'file' => ['required', 'file', 'max:51200'], // 50 MB
        ]);

        $file      = $request->file('file');
        $mime      = $file->getMimeType() ?? 'application/octet-stream';
        $type      = $this->resolveType($mime);
        $ext       = $file->getClientOriginalExtension();
        $yearMonth = now()->format('Y/m');
        $dir       = "communications/{$yearMonth}";

        // Store in storage/app/public/communications/YYYY/MM/
        $storedPath = $file->store($dir, 'public');

        if (! $storedPath) {
            Log::error('AttachmentUpload: storage failed', [
                'dir'  => $dir,
                'mime' => $mime,
            ]);
            return response()->json(['message' => 'File storage failed.'], 500);
        }

        // Build public URL — works both locally (storage symlink) and on production
        $url = Storage::disk('public')->url($storedPath);

        Log::info('AttachmentUpload: file stored', [
            'path' => $storedPath,
            'url'  => $url,
            'type' => $type,
            'size' => $file->getSize(),
        ]);

        $attachment = MessageAttachment::create([
            'message_type'    => 'pending',
            'message_id'      => null,
            'type'            => $type,
            'file_name'       => $file->getClientOriginalName(),
            'file_url'        => $url,
            'mime_type'       => $mime,
            'file_size_bytes' => $file->getSize(),
        ]);

        Log::info('AttachmentUpload: record created', ['id' => $attachment->id]);

        return response()->json([
            'attachment' => [
                'id'       => $attachment->id,
                'url'      => $url,
                'file_url' => $url,
                'type'     => $type,
                'name'     => $file->getClientOriginalName(),
                'size'     => $file->getSize(),
                'mime'     => $mime,
            ],
        ], 201);
    }

    private function resolveType(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            default                          => 'document',
        };
    }
}