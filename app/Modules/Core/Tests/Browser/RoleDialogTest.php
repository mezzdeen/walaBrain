<?php

use App\Modules\Core\Enums\OrganizationRole;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Support\OrganizationRoles;

/*
|--------------------------------------------------------------------------
| Role Dialog Browser Tests
|--------------------------------------------------------------------------
|
| The create and edit forms live in a dialog that stays on the page between
| opens. Its fields are only correct if every open starts from the current
| role rather than from whatever the previous open left behind, so these drive
| the compiled bundle to prove the dialog resets rather than asserting props.
|
*/

test('the new role form is empty again after a role is created', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);

    $this->actingAs($owner);

    visit(route('roles.index', absolute: false))
        ->click('@role-create-trigger')
        ->fill('@role-name-input', 'auditor')
        ->click('@permission-members-view')
        ->click('@role-submit')
        ->waitForEvent('networkidle')
        ->assertSee('auditor')
        // Reopening must not carry over the name and permission just submitted.
        ->click('@role-create-trigger')
        ->assertValue('@role-name-input', '')
        ->assertAttribute('@permission-members-view', 'data-state', 'unchecked')
        ->assertNoJavaScriptErrors();
});

test('editing a role shows the saved values when the dialog is reopened', function () {
    $organization = Organization::factory()->create();
    $owner = memberOf($organization, OrganizationRole::Owner);
    $role = OrganizationRoles::within($organization, fn () => tap(Role::create([
        'name' => 'auditor',
        'guard_name' => 'web',
        'organization_id' => $organization->id,
    ]))->syncPermissions([Permission::findOrCreate('members.view', 'web')]));

    $this->actingAs($owner);

    visit(route('roles.index', absolute: false))
        ->click("@role-edit-{$role->hash_id}")
        // The dialog opens on the role's own name and permissions.
        ->assertValue('@role-name-input', 'auditor')
        ->assertAttribute('@permission-members-view', 'data-state', 'checked')
        ->fill('@role-name-input', 'reviewer')
        ->click('@permission-roles-view')
        ->click('@role-submit')
        ->waitForEvent('networkidle')
        ->assertSee('reviewer')
        // Reopening shows what was saved, not the state the first open left.
        ->click("@role-edit-{$role->hash_id}")
        ->assertValue('@role-name-input', 'reviewer')
        ->assertAttribute('@permission-members-view', 'data-state', 'checked')
        ->assertAttribute('@permission-roles-view', 'data-state', 'checked')
        ->assertNoJavaScriptErrors();

    expect($role->fresh()->permissions->pluck('name')->all())
        ->toContain('members.view', 'roles.view');
});
