<?php

namespace Tests\Feature;

use App\Broadcasting\ConversationChannel;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_search_lists_other_accounts_including_suspended_and_blocks_self_messages(): void
    {
        $sender = $this->makeUser('sender@example.test');
        $other = $this->makeUser('other@example.test');
        $suspended = $this->makeUser('suspended@example.test', false);

        $this->withSession(['login_user' => ['id' => $sender->id]])
            ->getJson(route('messages.accounts').'?q=example.test')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $other->id, 'can_message' => true, 'status' => 'Active'])
            ->assertJsonFragment(['id' => $suspended->id, 'can_message' => false, 'status' => 'Suspended']);

        $this->withSession(['login_user' => ['id' => $sender->id]])
            ->postJson(route('messages.conversations.create'), ['target_user_id' => $sender->id])
            ->assertUnprocessable();

        $this->withSession(['login_user' => ['id' => $sender->id]])
            ->postJson(route('messages.conversations.create'), ['target_user_id' => $suspended->id])
            ->assertForbidden()
            ->assertJsonPath('message', "Can't message this user, this account has been suspended.");
    }

    public function test_account_search_matches_full_names_and_secretary_role(): void
    {
        $sender = $this->makeUser('sender@example.test');
        $secretary = User::query()->create([
            'gmail' => 'taylor.secretary@example.test',
            'password' => 'password',
            'first_name' => 'Taylor',
            'last_name' => 'Secretary',
            'tungkulin' => 'Secretary',
            'is_verified' => true,
        ]);

        $this->withSession(['login_user' => ['id' => $sender->id]])
            ->getJson(route('messages.accounts').'?q=Taylor%20Secretary')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $secretary->id)
            ->assertJsonPath('0.role', 'Secretary');

        $this->withSession(['login_user' => ['id' => $sender->id]])
            ->getJson(route('messages.accounts').'?q=Secretary')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $secretary->id);
    }

    public function test_account_and_conversation_payloads_include_public_profile_photo_urls(): void
    {
        Storage::fake('public');
        $sender = $this->makeUser('sender@example.test');
        $recipient = $this->makeUser('recipient@example.test');
        $photoPath = $this->pngUpload('profile.png')->store('profile-pictures', 'public');
        $recipient->forceFill(['profile_picture' => $photoPath])->save();
        $this->conversation($sender, $recipient);
        $photoUrl = '/storage/'.$photoPath;

        $this->withSession(['login_user' => ['id' => $sender->id]])
            ->getJson(route('messages.accounts').'?q=recipient')
            ->assertOk()
            ->assertJsonPath('0.avatar_url', $photoUrl);

        $this->withSession(['login_user' => ['id' => $sender->id]])
            ->getJson(route('messages.conversations'))
            ->assertOk()
            ->assertJsonPath('0.user.avatar_url', $photoUrl);
    }

    public function test_existing_conversation_shows_suspended_account_status_without_allowing_access(): void
    {
        $active = $this->makeUser('active@example.test');
        $suspended = $this->makeUser('suspended@example.test', false);
        $conversation = $this->conversation($active, $suspended);

        $this->withSession(['login_user' => ['id' => $active->id]])
            ->getJson(route('messages.conversations'))
            ->assertOk()
            ->assertJsonPath('0.user.id', $suspended->id)
            ->assertJsonPath('0.user.can_message', false)
            ->assertJsonPath('0.user.status', 'Suspended');

        $this->withSession(['login_user' => ['id' => $active->id]])
            ->getJson(route('messages.list', $conversation))
            ->assertForbidden()
            ->assertJsonPath('message', "Can't message this user, this account has been suspended.");
    }

    public function test_messages_are_opened_from_a_floating_launcher_not_the_sidebar_navigation(): void
    {
        $user = $this->makeUser('sender@example.test');

        $this->withSession(['login_user' => ['id' => $user->id]])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('class="chat-head-launcher"', false)
            ->assertSee('aria-label="Open messages"', false)
            ->assertDontSee('<span class="label">Messages</span>', false);
    }

    public function test_messages_support_private_images_receipts_and_participant_authorization(): void
    {
        Storage::fake('local');
        $sender = $this->makeUser('sender@example.test');
        $recipient = $this->makeUser('recipient@example.test');
        $outsider = $this->makeUser('outsider@example.test');
        $conversation = $this->conversation($sender, $recipient);

        $this->withSession(['login_user' => ['id' => $sender->id]])
            ->postJson(route('messages.send', $conversation), ['body' => 'Hello 👋'])
            ->assertCreated()
            ->assertJsonPath('body', 'Hello 👋');
        $sentMessage = Message::query()->where('conversation_id', $conversation->id)->firstOrFail();
        $broadcastChannels = (new MessageSent($sentMessage))->broadcastOn();
        $this->assertSame('private-conversation.'.$conversation->id, $broadcastChannels[0]->name);
        $this->assertSame('private-user.'.$recipient->id, $broadcastChannels[1]->name);

        $image = $this->pngUpload('private.png');
        $response = $this->withSession(['login_user' => ['id' => $sender->id]])
            ->post(route('messages.send', $conversation), ['attachment' => $image]);
        $response->assertCreated();
        $imageMessage = Message::query()->findOrFail($response->json('id'));
        Storage::disk('local')->assertExists($imageMessage->attachment_path);

        $this->withSession(['login_user' => ['id' => $outsider->id]])
            ->getJson(route('messages.list', $conversation))
            ->assertNotFound();
        $this->withSession(['login_user' => ['id' => $outsider->id]])
            ->postJson(route('messages.send', $conversation), ['body' => 'Unauthorized'])
            ->assertNotFound();
        $this->withSession(['login_user' => ['id' => $outsider->id]])
            ->get(route('messages.attachment', $imageMessage))
            ->assertNotFound();

        $textMessage = Message::query()->where('conversation_id', $conversation->id)
            ->whereNotNull('body')->firstOrFail();
        $this->withSession(['login_user' => ['id' => $recipient->id]])
            ->postJson(route('messages.delivered', $textMessage))
            ->assertOk();
        $this->assertNotNull($textMessage->fresh()->delivered_at);

        $this->withSession(['login_user' => ['id' => $recipient->id]])
            ->getJson(route('messages.list', $conversation))
            ->assertOk()->assertJsonCount(2);
        $this->assertNotNull($textMessage->fresh()->read_at);

        $this->withSession(['login_user' => ['id' => $sender->id]])
            ->get(route('messages.attachment', $imageMessage))
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertHeader('content-disposition', 'inline; filename=private.png');
    }

    public function test_upload_rejects_non_image_files_and_cleanup_removes_expired_messages_and_files(): void
    {
        Storage::fake('local');
        $sender = $this->makeUser('sender@example.test');
        $recipient = $this->makeUser('recipient@example.test');
        $conversation = $this->conversation($sender, $recipient);

        $this->withSession(['login_user' => ['id' => $sender->id]])
            ->postJson(route('messages.send', $conversation), [
                'attachment' => UploadedFile::fake()->create('payload.pdf', 10, 'application/pdf'),
            ])
            ->assertUnprocessable();

        $response = $this->withSession(['login_user' => ['id' => $sender->id]])
            ->post(route('messages.send', $conversation), ['attachment' => $this->pngUpload('expired.png')]);
        $message = Message::query()->findOrFail($response->json('id'));
        $path = $message->attachment_path;
        Storage::disk('local')->assertExists($path);
        $message->forceFill(['expires_at' => now()->subSecond()])->save();

        $this->artisan('messages:cleanup-expired')->assertSuccessful();

        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
    }

    public function test_private_channel_authorization_uses_the_custom_login_session(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'messaging-test-key',
            'broadcasting.connections.reverb.secret' => 'messaging-test-secret',
            'broadcasting.connections.reverb.app_id' => 'messaging-test-app',
        ]);
        $sender = $this->makeUser('sender@example.test');
        $recipient = $this->makeUser('recipient@example.test');
        $outsider = $this->makeUser('outsider@example.test');
        $conversation = $this->conversation($sender, $recipient);
        $this->assertTrue((new ConversationChannel)->join($recipient, (int) $conversation->id));
        $channel = 'private-conversation.'.$conversation->id;

        $this->withSession(['login_user' => ['id' => $recipient->id]])
            ->postJson(route('broadcasting.auth'), ['socket_id' => '123.456', 'channel_name' => $channel])
            ->assertOk()
            ->assertJsonStructure(['auth']);

        $this->withSession(['login_user' => ['id' => $recipient->id]])
            ->postJson(route('broadcasting.auth'), ['socket_id' => '123.457', 'channel_name' => 'private-user.'.$recipient->id])
            ->assertOk()
            ->assertJsonStructure(['auth']);

        $this->withSession(['login_user' => ['id' => $outsider->id]])
            ->postJson(route('broadcasting.auth'), ['socket_id' => '123.789', 'channel_name' => $channel])
            ->assertForbidden();

        $this->withSession(['login_user' => ['id' => $outsider->id]])
            ->postJson(route('broadcasting.auth'), ['socket_id' => '123.790', 'channel_name' => 'private-user.'.$recipient->id])
            ->assertForbidden();
    }

    private function makeUser(string $email, bool $verified = true): User
    {
        return User::query()->create([
            'gmail' => $email,
            'password' => 'password',
            'first_name' => ucfirst(strstr($email, '@', true)),
            'last_name' => 'Test',
            'is_verified' => $verified,
        ]);
    }

    private function conversation(User $one, User $two): Conversation
    {
        return Conversation::query()->create([
            'user_one_id' => min($one->id, $two->id),
            'user_two_id' => max($one->id, $two->id),
        ]);
    }

    private function pngUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/pWQAAAAASUVORK5CYII='),
        );
    }
}
