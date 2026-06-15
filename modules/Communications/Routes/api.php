<?php

use Illuminate\Support\Facades\Route;
use Modules\Communications\Http\Controllers\Api\BroadcastController;
use Modules\Communications\Http\Controllers\Api\CommunityController;
use Modules\Communications\Http\Controllers\Api\DirectMessageController;
use Modules\Communications\Http\Controllers\Api\GroupController;
use Modules\Communications\Http\Controllers\Api\PresenceController;
use Modules\Communications\Http\Controllers\Api\MessageAttachmentController;

Route::middleware('auth:sanctum')->prefix('communications')->name('communications.')->group(function () {

    // ── Direct Messages ────────────────────────────────────────
    Route::get('conversations', [DirectMessageController::class, 'conversations'])->name('conversations.index');
    Route::post('conversations', [DirectMessageController::class, 'startConversation'])->name('conversations.start');
    Route::get('conversations/{id}/messages', [DirectMessageController::class, 'messages'])->name('conversations.messages');
    Route::post('conversations/{id}/messages', [DirectMessageController::class, 'send'])->name('conversations.send');
    Route::post('conversations/{id}/read', [DirectMessageController::class, 'markRead'])->name('conversations.read');
    Route::delete('messages/dm/{id}/me', [DirectMessageController::class, 'deleteForMe'])->name('messages.dm.delete-me');
    Route::delete('messages/dm/{id}/everyone', [DirectMessageController::class, 'deleteForEveryone'])->name('messages.dm.delete-everyone');
    Route::post('messages/dm/{id}/react', [DirectMessageController::class, 'react'])->name('messages.dm.react');
    Route::get('conversations/{id}', [DirectMessageController::class, 'show'])->name('conversations.show');
    Route::post('conversations/{id}/close', [DirectMessageController::class, 'close'])->name('conversations.close');
    Route::post('conversations/{id}/reopen', [DirectMessageController::class, 'reopen'])->name('conversations.reopen');
    Route::patch('messages/dm/{id}', [DirectMessageController::class, 'editMessage'])->name('messages.dm.edit');
    Route::post('messages/dm/{id}/pin', [DirectMessageController::class, 'pinMessage'])->name('messages.dm.pin');
    Route::post('messages/dm/{id}/star', [DirectMessageController::class, 'starMessage'])->name('messages.dm.star');
    Route::get('messages/dm/{id}/receipts', [DirectMessageController::class, 'receipts'])->name('messages.dm.receipts');

    // ── Message Attachments ───────────────────────────────────
    Route::post('attachments/upload', [MessageAttachmentController::class, 'upload'])->name('attachments.upload');

    // ── Groups ─────────────────────────────────────────────────
    Route::post('groups', [GroupController::class, 'store'])->name('groups.store');
    Route::get('groups/{id}', [GroupController::class, 'show'])->name('groups.show');
    Route::get('groups/{id}/messages', [GroupController::class, 'messages'])->name('groups.messages');
    Route::post('groups/{id}/messages', [GroupController::class, 'send'])->name('groups.send');
    Route::post('groups/{id}/read', [GroupController::class, 'markRead'])->name('groups.read');
    Route::post('groups/{id}/participants', [GroupController::class, 'addParticipant'])->name('groups.participants.add');
    Route::delete('groups/{id}/participants/{actorId}', [GroupController::class, 'removeParticipant'])->name('groups.participants.remove');
    Route::post('groups/{id}/participants/{actorId}/promote', [GroupController::class, 'promote'])->name('groups.participants.promote');
    Route::delete('messages/group/{messageId}/everyone', [GroupController::class, 'deleteForEveryone'])->name('messages.group.delete-everyone');
    Route::post('messages/group/{messageId}/react', [GroupController::class, 'react'])->name('messages.group.react');
    Route::get('groups', [GroupController::class, 'index'])->name('groups.index');
    Route::post('groups/{id}/close', [GroupController::class, 'close'])->name('groups.close');
    Route::post('groups/{id}/reopen', [GroupController::class, 'reopen'])->name('groups.reopen');
    Route::patch('messages/group/{messageId}', [GroupController::class, 'editMessage'])->name('messages.group.edit');
    Route::post('messages/group/{messageId}/pin', [GroupController::class, 'pinMessage'])->name('messages.group.pin');
    Route::post('messages/group/{messageId}/star', [GroupController::class, 'starMessage'])->name('messages.group.star');
    Route::get('messages/group/{messageId}/receipts', [GroupController::class, 'receipts'])->name('messages.group.receipts');

    // ── Broadcasts ─────────────────────────────────────────────
    Route::get('broadcasts', [BroadcastController::class, 'index'])->name('broadcasts.index');
    Route::post('broadcasts', [BroadcastController::class, 'store'])->name('broadcasts.store');
    Route::get('broadcasts/{id}/messages', [BroadcastController::class, 'messages'])->name('broadcasts.messages');
    Route::post('broadcasts/{id}/messages', [BroadcastController::class, 'send'])->name('broadcasts.send');
    Route::post('broadcasts/{id}/recipients', [BroadcastController::class, 'addRecipient'])->name('broadcasts.recipients.add');
    Route::delete('broadcasts/{id}/recipients/{actorId}', [BroadcastController::class, 'removeRecipient'])->name('broadcasts.recipients.remove');

    // ── Communities ────────────────────────────────────────────
    Route::get('communities', [CommunityController::class, 'index'])->name('communities.index');
    Route::post('communities', [CommunityController::class, 'store'])->name('communities.store');
    Route::get('communities/{id}', [CommunityController::class, 'show'])->name('communities.show');
    Route::post('communities/{id}/groups', [CommunityController::class, 'addGroup'])->name('communities.groups.add');
    Route::delete('communities/{id}/groups/{groupId}', [CommunityController::class, 'removeGroup'])->name('communities.groups.remove');
    Route::post('communities/{id}/join', [CommunityController::class, 'join'])->name('communities.join');

    // ── Presence ───────────────────────────────────────────────
    Route::post('presence/online', [PresenceController::class, 'online'])->name('presence.online');
    Route::post('presence/offline', [PresenceController::class, 'offline'])->name('presence.offline');
    Route::get('presence/{actorId}', [PresenceController::class, 'show'])->name('presence.show');
    Route::post('presence/bulk', [PresenceController::class, 'bulk'])->name('presence.bulk');
});
