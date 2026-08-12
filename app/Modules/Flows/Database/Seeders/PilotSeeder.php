<?php

namespace App\Modules\Flows\Database\Seeders;

use App\Modules\Boards\Enums\FieldType;
use App\Modules\Boards\Models\Board;
use App\Modules\Boards\Models\Field;
use App\Modules\Boards\Models\Group;
use App\Modules\Boards\Support\Boards;
use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Space;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\OrganizationContext;
use App\Modules\Core\Support\OrganizationMembers;
use App\Modules\Core\Support\Spaces;
use App\Modules\Flows\Enums\StepType;
use App\Modules\Flows\Models\Flow;
use App\Modules\Forms\Models\Form;
use Illuminate\Database\Seeder;

/**
 * The finance payment request, end to end, on the demo organization: the
 * Phase 1 pilot as 10-example-processes.md describes it, minus the branch the
 * next phase adds. A fixed two-approver chain — the requester's manager, then
 * the finance manager — and an execution task due three calendar days after
 * submission.
 *
 * Local only, for the same reason the demo accounts are: it hangs the pilot
 * on organizations whose passwords this repository publishes.
 */
class PilotSeeder extends Seeder
{
    /**
     * Seed the pilot process onto the demo organization.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $organization = Organization::query()->firstWhere('name', 'Nakheel');
        $asmaa = User::query()->firstWhere('email', 'asmaa@app.com');
        $tysier = User::query()->firstWhere('email', 'tysier@app.com');

        if ($organization === null || $asmaa === null || $tysier === null) {
            return;
        }

        // The pilot's first approver is "the requester's manager", which is
        // nobody until a reporting line exists. Asmaa manages Tysier; Asmaa
        // herself reports to no one, which is also worth having on a demo —
        // submitting as her shows what a missing manager looks like.
        OrganizationMembers::setManager($organization, $tysier, $asmaa);

        Spaces::provisionDefault($organization);
        Boards::provisionDefault($organization);

        OrganizationContext::for($organization, function () use ($organization, $asmaa): void {
            $space = Space::query()->firstOrCreate(
                ['name' => 'Finance', 'organization_id' => $organization->getKey()],
                ['position' => 1],
            );

            $board = Board::query()->firstOrCreate(
                ['name' => 'Payment Requests', 'organization_id' => $organization->getKey()],
                ['space_id' => $space->getKey(), 'position' => 0],
            );

            // organization_id is written out in every create below, and not
            // because the trait would not stamp it: DatabaseSeeder mutes model
            // events for every seeder it calls, so relying on the hook here
            // works from `--class` and fails from `db:seed` — the worst kind
            // of difference.
            foreach (['New', 'In review', 'Done'] as $position => $name) {
                Group::query()->firstOrCreate(
                    ['name' => $name, 'board_id' => $board->getKey()],
                    ['position' => $position, 'organization_id' => $organization->getKey()],
                );
            }

            $fields = [
                ['name' => 'Type', 'type' => FieldType::SingleSelect, 'options' => ['pay', 'collect'], 'is_required' => true],
                ['name' => 'Amount', 'type' => FieldType::Money, 'options' => null, 'is_required' => true],
                ['name' => 'Beneficiary', 'type' => FieldType::Text, 'options' => null, 'is_required' => true],
                ['name' => 'Cost center', 'type' => FieldType::SingleSelect, 'options' => ['operations', 'marketing', 'people'], 'is_required' => true],
            ];

            foreach ($fields as $position => $field) {
                Field::query()->firstOrCreate(
                    ['name' => $field['name'], 'board_id' => $board->getKey()],
                    [
                        'type' => $field['type'],
                        'options' => $field['options'],
                        'is_required' => $field['is_required'],
                        'position' => $position,
                        'organization_id' => $organization->getKey(),
                    ],
                );
            }

            $form = Form::query()->firstOrCreate(
                ['prefix' => 'FIN', 'organization_id' => $organization->getKey()],
                [
                    'board_id' => $board->getKey(),
                    'name' => 'Payment Request',
                    'published_at' => now(),
                ],
            );

            $flow = Flow::query()->firstOrCreate(
                ['form_id' => $form->getKey()],
                [
                    'board_id' => $board->getKey(),
                    'name' => 'Payment approval',
                    'published_at' => now(),
                    'organization_id' => $organization->getKey(),
                ],
            );

            if ($flow->steps()->doesntExist()) {
                $flow->steps()->createMany([
                    [
                        'organization_id' => $organization->getKey(),
                        'position' => 1,
                        'type' => StepType::Approval,
                        'config' => ['assignee_type' => 'manager'],
                    ],
                    [
                        'organization_id' => $organization->getKey(),
                        'position' => 2,
                        'type' => StepType::Approval,
                        'config' => ['assignee_type' => 'user', 'assignee_id' => $asmaa->getKey()],
                    ],
                    [
                        'organization_id' => $organization->getKey(),
                        'position' => 3,
                        'type' => StepType::Task,
                        'config' => [
                            'assignee_type' => 'user',
                            'assignee_id' => $asmaa->getKey(),
                            'title' => 'Execute payment',
                            'due_offset_days' => 3,
                        ],
                    ],
                ]);
            }
        });
    }
}
