<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_upload_an_avatar()
    {
        Storage::fake('r2');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this
            ->actingAs($user)
            ->post('/settings/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        Storage::disk('r2')->assertExists($user->avatar_path);
    }

    public function test_unverified_user_cannot_upload_an_avatar()
    {
        Storage::fake('r2');

        $user = User::factory()->unverified()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/profile')
            ->post('/settings/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            ]);

        $response->assertRedirect('/settings/profile');

        $this->assertNull($user->refresh()->avatar_path);
    }

    public function test_uploading_a_new_avatar_deletes_the_previous_one()
    {
        Storage::fake('r2');

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'avatar_path' => 'avatars/old.jpg',
        ]);
        Storage::disk('r2')->put('avatars/old.jpg', 'fake-content');

        $this
            ->actingAs($user)
            ->post('/settings/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('new.jpg'),
            ]);

        Storage::disk('r2')->assertMissing('avatars/old.jpg');
    }

    public function test_user_can_remove_their_avatar()
    {
        Storage::fake('r2');

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'avatar_path' => 'avatars/old.jpg',
        ]);
        Storage::disk('r2')->put('avatars/old.jpg', 'fake-content');

        $response = $this
            ->actingAs($user)
            ->delete('/settings/profile/avatar');

        $response->assertRedirect('/settings/profile');

        $this->assertNull($user->refresh()->avatar_path);
        Storage::disk('r2')->assertMissing('avatars/old.jpg');
    }
}
