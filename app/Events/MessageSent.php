<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
        $this->message->loadMissing('sender');
    }

    public function broadcastOn(): array
    {
        $conversation = $this->message->conversation;
        $recipientId = (int) $conversation->user_one_id === (int) $this->message->sender_id
            ? $conversation->user_two_id
            : $conversation->user_one_id;

        return [
            new PrivateChannel('conversation.'.$this->message->conversation_id),
            new PrivateChannel('user.'.$recipientId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return ['message' => [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender?->full_name,
            'body' => $this->message->body,
            'attachment_url' => $this->message->attachment_path ? route('messages.attachment', $this->message) : null,
            'attachment_name' => $this->message->attachment_name,
            'created_at' => $this->message->created_at?->toISOString(),
            'delivered_at' => $this->message->delivered_at?->toISOString(),
            'read_at' => $this->message->read_at?->toISOString(),
        ]];
    }
}
