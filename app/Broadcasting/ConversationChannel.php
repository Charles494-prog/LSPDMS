<?php

namespace App\Broadcasting;

use App\Models\Conversation;
use App\Models\User;

class ConversationChannel
{
    public function join(User $user, int $conversationId): bool
    {
        if (! $user->is_verified) {
            return false;
        }

        $conversation = Conversation::query()->find($conversationId);
        if (! $conversation || ! $conversation->includes((int) $user->id)) {
            return false;
        }

        $otherParticipantId = (int) $conversation->user_one_id === (int) $user->id
            ? $conversation->user_two_id
            : $conversation->user_one_id;

        return User::query()->whereKey($otherParticipantId)->where('is_verified', true)->exists();
    }
}
