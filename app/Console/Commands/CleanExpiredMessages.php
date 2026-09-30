<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanExpiredMessages extends Command
{
    protected $signature = 'messages:cleanup-expired';

    protected $description = 'Delete expired messages and their private attachments';

    public function handle(): int
    {
        Message::query()->where('expires_at', '<=', now())->orderBy('id')->chunkById(100, function ($messages): void {
            foreach ($messages as $message) {
                if ($message->attachment_path) {
                    Storage::disk('local')->delete($message->attachment_path);
                }

                $message->delete();
            }
        });

        Conversation::query()->whereDoesntHave('messages')->delete();

        $this->info('Expired messages and unused conversations have been removed.');

        return self::SUCCESS;
    }
}
