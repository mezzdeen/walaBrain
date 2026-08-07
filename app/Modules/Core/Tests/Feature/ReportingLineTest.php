<?php

use App\Modules\Core\Exceptions\InvalidReportingLine;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationMembers;

/*
|--------------------------------------------------------------------------
| Reporting Lines
|--------------------------------------------------------------------------
|
| Who reports to whom, per organization. Load-bearing before it looks it:
| the first approval step of both worked processes is assigned to "the
| requester's manager".
|
*/

test('a member reports to nobody until someone says otherwise', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);

    expect($member->managerIn($organization))->toBeNull();
});

test('a manager can be named and cleared', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);
    $lead = memberOf($organization);

    OrganizationMembers::setManager($organization, $member, $lead);

    expect($member->managerIn($organization)?->is($lead))->toBeTrue();

    OrganizationMembers::setManager($organization, $member, null);

    expect($member->managerIn($organization))->toBeNull();
});

test('a manager sees who reports to them', function () {
    $organization = Organization::factory()->create();
    $lead = memberOf($organization);
    $first = memberOf($organization);
    $second = memberOf($organization);
    $unrelated = memberOf($organization);

    OrganizationMembers::setManager($organization, $first, $lead);
    OrganizationMembers::setManager($organization, $second, $lead);

    expect($lead->directReportsIn($organization)->pluck('id')->sort()->values()->all())
        ->toBe([$first->id, $second->id])
        ->and($lead->managesInOrganization($first, $organization))->toBeTrue()
        ->and($lead->managesInOrganization($unrelated, $organization))->toBeFalse();
});

test('someone from another organization cannot be a manager here', function () {
    $organization = Organization::factory()->create();
    $elsewhere = Organization::factory()->create();

    $member = memberOf($organization);
    $outsider = memberOf($elsewhere);

    // A reporting line that crossed a business line would be the one thing in
    // the application that does.
    expect(fn () => OrganizationMembers::setManager($organization, $member, $outsider))
        ->toThrow(InvalidReportingLine::class);
});

test('nobody manages themselves', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);

    expect(fn () => OrganizationMembers::setManager($organization, $member, $member))
        ->toThrow(InvalidReportingLine::class);
});

test('a reporting line cannot be closed into a loop', function () {
    $organization = Organization::factory()->create();
    $bottom = memberOf($organization);
    $middle = memberOf($organization);
    $top = memberOf($organization);

    OrganizationMembers::setManager($organization, $bottom, $middle);
    OrganizationMembers::setManager($organization, $middle, $top);

    // Closing the chain would leave it with no top, and anything walking it
    // going round forever.
    expect(fn () => OrganizationMembers::setManager($organization, $top, $bottom))
        ->toThrow(InvalidReportingLine::class);
});

test('a long chain that does not loop is allowed', function () {
    $organization = Organization::factory()->create();
    $a = memberOf($organization);
    $b = memberOf($organization);
    $c = memberOf($organization);
    $d = memberOf($organization);

    OrganizationMembers::setManager($organization, $a, $b);
    OrganizationMembers::setManager($organization, $b, $c);
    OrganizationMembers::setManager($organization, $c, $d);

    expect($a->managerIn($organization)?->is($b))->toBeTrue()
        ->and($d->managerIn($organization))->toBeNull();
});

test('the same person reports to different people in different organizations', function () {
    $marketing = Organization::factory()->create();
    $finance = Organization::factory()->create();

    $person = memberOf($marketing);
    $finance->users()->attach($person);

    $marketingLead = memberOf($marketing);
    $financeLead = memberOf($finance);

    OrganizationMembers::setManager($marketing, $person, $marketingLead);
    OrganizationMembers::setManager($finance, $person, $financeLead);

    expect($person->managerIn($marketing)?->is($marketingLead))->toBeTrue()
        ->and($person->managerIn($finance)?->is($financeLead))->toBeTrue();
});

test('losing a manager leaves their reports in the organization', function () {
    $organization = Organization::factory()->create();
    $member = memberOf($organization);
    $lead = memberOf($organization);

    OrganizationMembers::setManager($organization, $member, $lead);

    $lead->forceDelete();

    expect($member->fresh()->belongsToOrganization($organization))->toBeTrue()
        ->and($member->managerIn($organization))->toBeNull();
});

test('a manager in one organization manages nobody in another', function () {
    $marketing = Organization::factory()->create();
    $finance = Organization::factory()->create();

    $lead = memberOf($marketing);
    $finance->users()->attach($lead);

    $report = memberOf($marketing);
    $finance->users()->attach($report);

    OrganizationMembers::setManager($marketing, $report, $lead);

    expect($lead->managesInOrganization($report, $marketing))->toBeTrue()
        ->and($lead->managesInOrganization($report, $finance))->toBeFalse()
        ->and($lead->directReportsIn($finance))->toBeEmpty();
});

test('a user who was never a member has no manager here', function () {
    $organization = Organization::factory()->create();
    $stranger = User::factory()->create();

    expect($stranger->managerIn($organization))->toBeNull();
});
