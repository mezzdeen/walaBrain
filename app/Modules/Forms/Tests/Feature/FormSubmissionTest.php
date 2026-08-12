<?php

use App\Modules\Boards\Enums\FieldType;
use App\Modules\Boards\Models\Board;
use App\Modules\Boards\Models\Field;
use App\Modules\Boards\Models\Node;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Forms\Models\Form;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| Form Submission
|--------------------------------------------------------------------------
|
| The front door: a submission creates a referenced node carrying typed
| values, atomically, and announces itself to whatever automates the rest.
|
*/

/**
 * A published form whose board carries the pilot's field shapes.
 *
 * @return array{0: Form, 1: Field, 2: Field}
 */
function pilotForm(Organization $organization): array
{
    return OrganizationContext::for($organization, function (): array {
        $board = Board::factory()->create();

        $amount = Field::factory()->on($board)->ofType(FieldType::Money)->create(['name' => 'Amount', 'is_required' => true]);
        $type = Field::factory()->on($board)->ofType(FieldType::SingleSelect, ['pay', 'collect'])->create(['name' => 'Type']);

        return [Form::factory()->on($board)->create(['prefix' => 'FIN']), $amount, $type];
    });
}

test('submitting creates a referenced node carrying the typed values', function () {
    $organization = Organization::factory()->create();
    $submitter = memberOf($organization);

    [$form, $amount, $type] = pilotForm($organization);

    OrganizationContext::switch($organization);

    $this->actingAs($submitter)
        ->post(route('forms.store', $form), [
            'values' => [
                $amount->hash_id => '1500.50',
                $type->hash_id => 'pay',
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $node = OrganizationContext::for($organization, fn () => Node::query()->firstOrFail());

    expect($node->reference)->toBe('FIN-'.now()->year.'-0001')
        ->and($node->creator_id)->toBe($submitter->getKey())
        ->and($node->status)->toBe('in_review')
        // Stored as a number, not the string that arrived: the storage
        // contract every sort depends on.
        ->and($node->valueFor($amount))->toBe(1500.5)
        ->and($node->valueFor($type))->toBe('pay')
        ->and($node->activities()->where('type', 'form.submitted')->count())->toBe(1);
});

test('the sequence counts up within a year and starts over in the next', function () {
    $organization = Organization::factory()->create();
    $submitter = memberOf($organization);

    [$form, $amount, $type] = pilotForm($organization);

    OrganizationContext::switch($organization);
    $this->actingAs($submitter);

    $submit = fn () => $this->post(route('forms.store', $form), [
        'values' => [$amount->hash_id => '10', $type->hash_id => 'pay'],
    ]);

    Carbon::setTestNow('2026-06-01 10:00:00');
    $submit();
    $submit();

    // A new year is a new count, not a continuation.
    Carbon::setTestNow('2027-01-05 10:00:00');
    $submit();
    Carbon::setTestNow();

    $references = OrganizationContext::for(
        $organization,
        fn () => Node::query()->orderBy('id')->pluck('reference')->all(),
    );

    expect($references)->toBe(['FIN-2026-0001', 'FIN-2026-0002', 'FIN-2027-0001']);
});

test('a required field blocks submission until it is filled in', function () {
    $organization = Organization::factory()->create();
    $submitter = memberOf($organization);

    [$form, $amount, $type] = pilotForm($organization);

    OrganizationContext::switch($organization);

    $this->actingAs($submitter)
        ->post(route('forms.store', $form), [
            'values' => [$type->hash_id => 'pay'],
        ])
        ->assertSessionHasErrors('values.'.$amount->hash_id);

    expect(OrganizationContext::for($organization, fn () => Node::count()))->toBe(0);
});

test('a money value that is not a number is refused, not stored', function () {
    $organization = Organization::factory()->create();
    $submitter = memberOf($organization);

    [$form, $amount, $type] = pilotForm($organization);

    OrganizationContext::switch($organization);

    $this->actingAs($submitter)
        ->post(route('forms.store', $form), [
            'values' => [$amount->hash_id => '1,200', $type->hash_id => 'pay'],
        ])
        ->assertSessionHasErrors('values.'.$amount->hash_id);
});

test('a choice outside the field\'s options is refused', function () {
    $organization = Organization::factory()->create();
    $submitter = memberOf($organization);

    [$form, $amount, $type] = pilotForm($organization);

    OrganizationContext::switch($organization);

    $this->actingAs($submitter)
        ->post(route('forms.store', $form), [
            'values' => [$amount->hash_id => '10', $type->hash_id => 'borrow'],
        ])
        ->assertSessionHasErrors('values.'.$type->hash_id);
});

test('a draft form accepts nothing', function () {
    $organization = Organization::factory()->create();
    $submitter = memberOf($organization);

    $form = OrganizationContext::for($organization, fn () => Form::factory()->draft()->create());

    OrganizationContext::switch($organization);

    $this->actingAs($submitter)->get(route('forms.show', $form))->assertForbidden();
    $this->actingAs($submitter)->post(route('forms.store', $form), ['values' => []])->assertForbidden();
});

test('a form from another business line does not exist here', function () {
    $mine = Organization::factory()->create();
    $theirs = Organization::factory()->create();

    $member = memberOf($mine);
    [$form] = pilotForm($theirs);

    OrganizationContext::switch($mine);

    $this->actingAs($member)->get(route('forms.show', $form))->assertNotFound();
});

test('the submitter can follow their own request on a board they cannot browse', function () {
    $organization = Organization::factory()->create();
    $submitter = memberOf($organization);

    [$form, $amount, $type] = pilotForm($organization);

    OrganizationContext::switch($organization);

    $this->actingAs($submitter)->post(route('forms.store', $form), [
        'values' => [$amount->hash_id => '10', $type->hash_id => 'pay'],
    ]);

    $node = OrganizationContext::for($organization, fn () => Node::query()->firstOrFail());

    // Not a member of the board's space — intake deliberately does not
    // require that — yet their own submission stays visible to them.
    $this->get(route('nodes.show', $node))->assertOk();
});
