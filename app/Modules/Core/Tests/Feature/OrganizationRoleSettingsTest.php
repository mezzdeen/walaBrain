<?php

use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\OrganizationRoles;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A member of the organization holding exactly the given organization
 * permissions and nothing else, for asserting what a limited member may do.
 */
function memberHolding(Organization $organization, OrganizationPermission ...$permissions): User
{
    $role = OrganizationRoles::within($organization, fn () => tap(Role::create([
        'name' => 'limited-'.Str::random(6),
        'guard_name' => 'web',
        'organization_id' => $organization->getKey(),
    ]))->syncPermissions(array_map(
        fn (OrganizationPermission $permission) => Permission::findOrCreate($permission->value, 'web'),
        $permissions,
    )));

    $user = User::factory()->create();
    $user->organizations()->attach($organization);

    setPermissionsTeamId($organization->getKey());
    $user->assignRole($role);

    return $user;
}

test('an owner can view their organization roles', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->get(route('roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('roles/index')
            ->has('roles', count(OrganizationRole::cases()))
            ->has('permissionGroups')
        );
});

test('the page only lists roles the current organization owns', function () {
    [$first, $second] = Organization::factory()->count(2)->create()->all();
    $owner = memberOf($first, OrganizationRole::Owner);
    OrganizationRoles::provision($second);
    OrganizationRoles::within($second, fn () => Role::create([
        'name' => 'auditor',
        'guard_name' => 'web',
        'organization_id' => $second->id,
    ]));

    $this->actingAs($owner)
        ->get(route('roles.index'))
        ->assertInertia(fn (Assert $page) => $page->has('roles', count(OrganizationRole::cases())));
});

test('a member can not view the roles', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization, OrganizationRole::Member);

    $this->actingAs($member)
        ->get(route('roles.index'))
        ->assertForbidden();
});

// Sent away rather than refused: with no organization there is no set of roles
// this screen could even be about, so the explanation is more use than a 403.
test('a user with no organization is sent away from the roles', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('roles.index'))
        ->assertRedirect(route('organizations.none'));
});

test('an owner can create a role for their organization', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->post(route('roles.store'), [
            'name' => 'auditor',
            'permissions' => [OrganizationPermission::ViewMembers->value],
        ])
        ->assertRedirect(route('roles.index'));

    $role = Role::query()->firstWhere('name', 'auditor');

    expect($role->organization_id)->toBe($organization->id)
        ->and($role->guard_name)->toBe('web')
        ->and($role->permissions->pluck('name')->all())
        ->toBe([OrganizationPermission::ViewMembers->value]);
});

test('a role name must be unique within the organization', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->from(route('roles.index'))
        ->post(route('roles.store'), ['name' => OrganizationRole::Member->value])
        ->assertSessionHasErrors('name');
});

test('two organizations may each have a role of the same name', function () {
    [$first, $second] = Organization::factory()->count(2)->create()->all();
    $owner = memberOf($first, OrganizationRole::Owner);
    OrganizationRoles::provision($second);
    OrganizationRoles::within($second, fn () => Role::create([
        'name' => 'auditor',
        'guard_name' => 'web',
        'organization_id' => $second->id,
    ]));

    $this->actingAs($owner)
        ->post(route('roles.store'), ['name' => 'auditor'])
        ->assertSessionHasNoErrors();

    expect(Role::query()->where('name', 'auditor')->count())->toBe(2);
});

test('a super guard permission can not be granted to an organization role', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->from(route('roles.index'))
        ->post(route('roles.store'), [
            'name' => 'auditor',
            'permissions' => ['organizations.delete'],
        ])
        ->assertSessionHasErrors('permissions.0');
});

test('an owner can update a role', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);
    $role = OrganizationRoles::within($organization, fn () => Role::create([
        'name' => 'auditor',
        'guard_name' => 'web',
        'organization_id' => $organization->id,
    ]));

    $this->actingAs($owner)
        ->put(route('roles.update', $role), [
            'name' => 'reviewer',
            'permissions' => [OrganizationPermission::ViewMembers->value],
        ])
        ->assertRedirect(route('roles.index'));

    expect($role->fresh()->name)->toBe('reviewer');
});

test('an owner can delete a role', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);
    $role = OrganizationRoles::within($organization, fn () => Role::create([
        'name' => 'auditor',
        'guard_name' => 'web',
        'organization_id' => $organization->id,
    ]));

    $this->actingAs($owner)
        ->delete(route('roles.destroy', $role))
        ->assertRedirect(route('roles.index'));

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

test('the owner role can not be deleted', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);
    $role = Role::query()
        ->where('organization_id', $organization->id)
        ->firstWhere('name', OrganizationRole::Owner->value);

    $this->actingAs($owner)
        ->delete(route('roles.destroy', $role))
        ->assertForbidden();

    $this->assertDatabaseHas('roles', ['id' => $role->id]);
});

test('an owner can not touch another organization role', function () {
    [$first, $second] = Organization::factory()->count(2)->create()->all();
    $owner = memberOf($first, OrganizationRole::Owner);
    OrganizationRoles::provision($second);
    $foreign = OrganizationRoles::within($second, fn () => Role::create([
        'name' => 'auditor',
        'guard_name' => 'web',
        'organization_id' => $second->id,
    ]));

    $this->actingAs($owner)
        ->put(route('roles.update', $foreign), ['name' => 'hijacked'])
        ->assertForbidden();

    $this->actingAs($owner)
        ->delete(route('roles.destroy', $foreign))
        ->assertForbidden();

    expect($foreign->fresh()->name)->toBe('auditor');
});

test('an owner can not touch a super platform role', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);
    seedPermissions();
    $platformRole = Role::query()->where('guard_name', 'super')->first();

    $this->actingAs($owner)
        ->delete(route('roles.destroy', $platformRole))
        ->assertForbidden();

    $this->assertDatabaseHas('roles', ['id' => $platformRole->id]);
});

test('permissions follow the organization the owner is acting on', function () {
    [$first, $second] = Organization::factory()->count(2)->create()->all();
    $user = memberOf($first, OrganizationRole::Owner);
    OrganizationRoles::provision($second);
    $user->organizations()->attach($second);
    OrganizationRoles::within($second, fn () => $user->assignRole(OrganizationRole::Member->value));

    // Owner in the first, member in the second: the same screen is allowed in
    // one and refused in the other.
    $this->actingAs($user)
        ->withSession([OrganizationContext::SESSION_KEY => $first->id])
        ->get(route('roles.index'))
        ->assertOk();

    $this->actingAs($user)
        ->withSession([OrganizationContext::SESSION_KEY => $second->id])
        ->get(route('roles.index'))
        ->assertForbidden();
});

test('guests can not access the role settings', function () {
    $this->get(route('roles.index'))->assertRedirect(route('login'));
});

test('a member can not grant a permission they do not hold when editing a role', function () {
    $organization = Organization::factory()->create();
    $editor = memberHolding($organization, OrganizationPermission::ViewRoles, OrganizationPermission::UpdateRoles);
    $role = OrganizationRoles::within($organization, fn () => Role::create([
        'name' => 'auditor',
        'guard_name' => 'web',
        'organization_id' => $organization->id,
    ]));

    $this->actingAs($editor)
        ->from(route('roles.index'))
        ->put(route('roles.update', $role), [
            'name' => 'auditor',
            // RemoveMembers is a real organization permission, but not one the
            // editor holds — so it must not be grantable through a role form.
            'permissions' => [OrganizationPermission::RemoveMembers->value],
        ])
        ->assertRedirect(route('roles.index'))
        ->assertSessionHasErrors('permissions.0');

    expect($role->fresh()->permissions->pluck('name'))
        ->not->toContain(OrganizationPermission::RemoveMembers->value);
});

test('a member can not grant a permission they do not hold when creating a role', function () {
    $organization = Organization::factory()->create();
    $creator = memberHolding($organization, OrganizationPermission::ViewRoles, OrganizationPermission::CreateRoles);

    $this->actingAs($creator)
        ->from(route('roles.index'))
        ->post(route('roles.store'), [
            'name' => 'auditor',
            'permissions' => [OrganizationPermission::RemoveMembers->value],
        ])
        ->assertSessionHasErrors('permissions.0');

    expect(Role::query()->where('organization_id', $organization->id)->where('name', 'auditor')->exists())
        ->toBeFalse();
});

test('a member can grant a permission they do hold', function () {
    $organization = Organization::factory()->create();
    $creator = memberHolding(
        $organization,
        OrganizationPermission::ViewRoles,
        OrganizationPermission::CreateRoles,
        OrganizationPermission::ViewMembers,
    );

    $this->actingAs($creator)
        ->from(route('roles.index'))
        ->post(route('roles.store'), [
            'name' => 'watcher',
            'permissions' => [OrganizationPermission::ViewMembers->value],
        ])
        ->assertRedirect(route('roles.index'))
        ->assertSessionHasNoErrors();

    $role = Role::query()->where('organization_id', $organization->id)->firstWhere('name', 'watcher');
    expect($role->permissions->pluck('name'))->toContain(OrganizationPermission::ViewMembers->value);
});

test('a member without the create permission can not create a role', function () {
    $organization = Organization::factory()->create();
    $viewer = memberHolding($organization, OrganizationPermission::ViewRoles);

    $this->actingAs($viewer)
        ->post(route('roles.store'), ['name' => 'auditor', 'permissions' => []])
        ->assertForbidden();

    expect(Role::query()->where('organization_id', $organization->id)->where('name', 'auditor')->exists())
        ->toBeFalse();
});
