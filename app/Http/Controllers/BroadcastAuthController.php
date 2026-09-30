<?php

namespace App\Http\Controllers;

use App\Broadcasting\ConversationChannel;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Http\Request;

class BroadcastAuthController extends Controller
{
    public function __invoke(Request $request, ConversationChannel $channel, BroadcastManager $broadcasting): mixed
    {
        $validated = $request->validate([
            'socket_id' => ['required', 'regex:/\A\d+\.\d+\z/'],
            'channel_name' => ['required', 'regex:/\Aprivate-(?:conversation|user)\.\d+\z/'],
        ]);
        $user = User::query()->whereKey($request->session()->get('login_user.id'))
            ->where('is_verified', true)->firstOrFail();
        if (str_starts_with($validated['channel_name'], 'private-user.')) {
            $channelUserId = (int) substr($validated['channel_name'], strlen('private-user.'));
            abort_unless($channelUserId === (int) $user->id, 403);
        } else {
            $conversationId = (int) substr($validated['channel_name'], strlen('private-conversation.'));
            abort_unless($channel->join($user, $conversationId), 403);
        }
        $authorization = $broadcasting->pusher(config('broadcasting.connections.reverb'))
            ->authorizeChannel($validated['channel_name'], $validated['socket_id']);

        return response()->json(json_decode($authorization, true, 512, JSON_THROW_ON_ERROR));
    }
}
