<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
