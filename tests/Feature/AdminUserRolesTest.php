<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_only_super_admins_can_view_the_users_page(): void
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');

        $this->actingAs($organizer)->get(route('admin.users.index'))->assertForbidden();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin)->get(route('admin.users.index'))->assertOk();
    }

    public function test_user_list_paginates_by_twenty_and_includes_users_without_a_club(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        // 25 more users (no club assigned) on top of the super admin itself = 26 total.
        User::factory()->count(25)->create(['club_id' => null]);

        $firstPage = $this->actingAs($superAdmin)->get(route('admin.users.index'));

        $firstPage->assertInertia(fn ($page) => $page
            ->component('admin/users/index')
            ->where('users.total', 26)
            ->where('users.per_page', 20)
            ->where('users.last_page', 2)
            ->has('users.data', 20)
        );

        $secondPage = $this->actingAs($superAdmin)->get(route('admin.users.index', ['page' => 2]));

        $secondPage->assertInertia(fn ($page) => $page
            ->component('admin/users/index')
            ->has('users.data', 6)
        );
    }

    public function test_super_admin_can_assign_and_remove_roles_for_another_user(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $target = User::factory()->create();
        $target->assignRole('player');

        $this->actingAs($superAdmin)
            ->patch(route('admin.users.roles.update', $target), ['roles' => ['player', 'referee']])
            ->assertRedirect();

        $target->refresh();
        $this->assertTrue($target->hasRole('player'));
        $this->assertTrue($target->hasRole('referee'));
        $this->assertFalse($target->hasRole('organizer'));
    }

    public function test_a_super_admin_cannot_remove_their_own_super_admin_role(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin)
            ->patch(route('admin.users.roles.update', $superAdmin), ['roles' => []])
            ->assertSessionHas('error');

        $this->assertTrue($superAdmin->fresh()->hasRole('super_admin'));
    }

    public function test_demoting_one_of_two_super_admins_is_allowed_since_one_remains(): void
    {
        $superAdmin1 = User::factory()->create();
        $superAdmin1->assignRole('super_admin');

        $superAdmin2 = User::factory()->create();
        $superAdmin2->assignRole('super_admin');

        $this->actingAs($superAdmin1)
            ->patch(route('admin.users.roles.update', $superAdmin2), ['roles' => ['organizer']])
            ->assertSessionMissing('error');

        $this->assertFalse($superAdmin2->fresh()->hasRole('super_admin'));
        $this->assertTrue($superAdmin1->fresh()->hasRole('super_admin'));
    }

    public function test_super_admin_can_soft_delete_another_user(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $target = User::factory()->create();
        $target->assignRole('player');

        $this->actingAs($superAdmin)->delete(route('admin.users.destroy', $target))->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->assertNull(User::find($target->id));
        $this->assertNotNull(User::withTrashed()->find($target->id));

        // Soft-deleted users disappear from the admin list and can no longer authenticate.
        $index = $this->actingAs($superAdmin)->get(route('admin.users.index'));
        $index->assertInertia(fn ($page) => $page->where('users.total', 1));

        $this->assertFalse(auth()->attempt(['email' => $target->email, 'password' => 'password']));
    }

    public function test_deleted_users_email_can_be_reused_to_register_a_new_account(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $target = User::factory()->create(['email' => 'jacobo@example.com']);

        $this->actingAs($superAdmin)->delete(route('admin.users.destroy', $target))->assertRedirect();

        $this->assertDatabaseMissing('users', ['email' => 'jacobo@example.com']);

        $this->post(route('logout'));

        $response = $this->post('/register', [
            'name' => 'Jacobo Nuevo',
            'email' => 'jacobo@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'jacobo@example.com', 'deleted_at' => null]);
    }

    public function test_a_super_admin_cannot_delete_their_own_account(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin)->delete(route('admin.users.destroy', $superAdmin))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'deleted_at' => null]);
    }

    public function test_deleting_one_of_two_super_admins_is_allowed_since_one_remains(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin)->delete(route('admin.users.destroy', $otherAdmin))
            ->assertSessionMissing('error');

        $this->assertSoftDeleted('users', ['id' => $otherAdmin->id]);
        $this->assertTrue($superAdmin->fresh()->hasRole('super_admin'));
    }

    public function test_regular_organizer_cannot_delete_users(): void
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');

        $target = User::factory()->create();

        $this->actingAs($organizer)->delete(route('admin.users.destroy', $target))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);
    }

    public function test_super_admin_can_create_a_user_with_a_temporary_password(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $response = $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'Jugador Nuevo',
            'email' => 'jugador-nuevo@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $created = User::where('email', 'jugador-nuevo@example.com')->first();
        $this->assertNotNull($created);
        $this->assertSame('Jugador Nuevo', $created->name);
        $this->assertNotNull($created->email_verified_at);
        $this->assertTrue(Hash::check(config('admin.default_reset_password'), $created->password));
    }

    public function test_creating_a_user_requires_a_unique_email(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $existing = User::factory()->create();

        $response = $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'Duplicado',
            'email' => $existing->email,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_regular_organizer_cannot_create_users(): void
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');

        $response = $this->actingAs($organizer)->post(route('admin.users.store'), [
            'name' => 'Intento',
            'email' => 'intento@example.com',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'intento@example.com']);
    }

    public function test_super_admin_can_reset_a_users_password_to_the_configured_default(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $target = User::factory()->create();

        $this->actingAs($superAdmin)->post(route('admin.users.reset-password', $target))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check(config('admin.default_reset_password'), $target->fresh()->password));
    }

    public function test_regular_organizer_cannot_reset_passwords(): void
    {
        $organizer = User::factory()->create();
        $organizer->assignRole('organizer');

        $target = User::factory()->create();
        $originalPassword = $target->password;

        $this->actingAs($organizer)->post(route('admin.users.reset-password', $target))->assertForbidden();

        $this->assertSame($originalPassword, $target->fresh()->password);
    }
}
