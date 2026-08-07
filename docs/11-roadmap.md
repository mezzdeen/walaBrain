# Roadmap & Rollout

## Purpose

This document lays out how the platform reaches every business line in five phases, starting with a single pilot process and expanding only once each phase proves itself against the spreadsheet or manual process it replaces. It defines the scope of each phase, how success is measured, the principles that govern rollout and adoption, and the open questions that must be answered before later phases can begin. See 00-overview.md for the platform vision and the full list of assumptions and open questions, 10-example-processes.md for the three worked examples this roadmap sequences, and 12-platform-foundation.md for the detail of what Phase 0 already delivered.

## Phased delivery

```mermaid
flowchart LR
    P0["Phase 0 Platform Foundation - built"] --> P1["Phase 1 Boards and Pilot"]
    P1 --> P2["Phase 2 Dynamic Flows"]
    P2 --> P3["Phase 3 Comms Calendar"]
    P3 --> P4["Phase 4 People Processes"]
    P4 --> P5["Phase 5 Insight"]
```

Phases are additive: each one keeps everything the previous phase delivered and adds new capability on top. A business line does not move to the next phase until the current one is working for its people.

| Phase | Focus | Pilot process | What retires |
|---|---|---|---|
| 0 — Platform Foundation | Tenancy, roles, invitations, sign-in, Arabic and English, admin platform | — | — |
| 1 — Boards & Pilot | Spaces, boards, forms, approvals, tasks, My Work | Finance payment request | Finance request tracking sheet |
| 2 — Dynamic Flows | Conditions, business-day deadlines, infeasibility checks | Marketing campaign request | Campaign planning sheet |
| 3 — Comms Calendar | Channels, slots, booking lifecycle | Campaign channel bookings | Channel booking sheet or calendar |
| 4 — People Processes | Onboarding, offboarding, activation windows, escalation | Onboarding and offboarding | HR onboarding and exit checklists |
| 5 — Insight | Reporting dashboards, SLA tracking | Cross-process reporting | Manually built reporting deck |

## Phase 0 — Platform Foundation

**Status**: built and tested. This phase is not a plan; it is what the following phases start from.

**Delivered**: business lines with enforced tenancy isolation; one account holding membership of several business lines with deliberate switching; custom roles and a capability matrix; invitations by email; sign-in with passkeys, password, and two-factor; Arabic and English throughout with right-to-left layout; the separate administration platform for Platform Admins; and platform settings.

**What it does not include**: anything in the five pillars. No spaces, boards, nodes, fields, forms, flows, approvals, tasks, comms calendar, audit timeline, or reporting exists yet. See 12-platform-foundation.md for the capabilities Phase 1 must add to the foundation before the pillars can be built on it — the append-only audit timeline in particular, which four of the five pillars write to.

## Phase 1 — Boards & Pilot

**Scope**:

- Spaces and space membership, boards, groups, and nodes. Business lines, custom roles, invitations, and sign-in already exist from Phase 0.
- The ten field types and the table view with filters.
- Forms that create nodes and issue reference numbers.
- Sequential approvals with approve, reject, and request-changes decisions. Approval chains are fixed at design time — threshold-based branching arrives with Phase 2's Condition steps.
- Tasks with deadlines anchored to the submission date, counted in calendar days. Business-day and holiday-aware counting arrives with Phase 2.
- My Work, the append-only activity timeline, and in-app and email notifications.

**Pilot**: a single process — the finance payment request (see 10-example-processes.md, story 2), run as a fixed two-approver chain (requester's manager, then finance manager) with a calendar-day, submission-relative due date. The amount-threshold branch and business-day deadline math shown in that story activate with Phase 2. One business line, one process, one spreadsheet retired.

**Success criteria**:

- The finance team's request-tracking spreadsheet is retired.
- Every payment and collection request is submitted, approved, and executed inside the platform.
- Requesters can see their request's status in My Work without asking finance directly.
- Every approval decision is recorded on the node's activity timeline.

- **US-11.1** — As a Business-Line Admin, I want to launch the platform with a single process, so that my team learns the platform without disrupting every process at once.
  - Given the finance request process is ready, when the pilot starts, then no other process is migrated in the same window.
  - The pilot business line's other processes continue in their existing spreadsheet or email flow until Phase 1 is judged successful.
- **US-11.2** — As an Employee, I want to submit a finance request through a form instead of a shared spreadsheet, so that my request is tracked and I know who has it.
  - Submitting the form creates a node with a reference number and starts the approval flow described in 05-flows.md.
  - I can see my request's status in My Work at any time.
- **US-11.3** — As an Approver, I want to decide finance approvals from My Work or an email deep link, so that I don't need to be shown a new tool to participate in the pilot.
  - Given an approval is waiting on me, when I open my email, then the link takes me straight to the decision screen described in 06-approvals.md.
  - Approving or rejecting records my decision on the node's activity timeline.
- **US-11.4** — As a Business-Line Admin, I want to confirm the finance spreadsheet is no longer needed, so that I can call the pilot a success and plan Phase 2.
  - Given a full month of finance requests ran through the platform, when I compare it against the retired spreadsheet, then every request has an equivalent node with a decision recorded.

## Phase 2 — Dynamic Flows

**Scope**:

- Condition steps that branch a flow on field values.
- Deadline anchors on a date field on the node, not only the submission date.
- Business-day and holiday-aware deadline math (see 07-tasks-and-deadlines.md).
- Parallel steps within a flow.
- Infeasible-timeline validation at submission.
- Target process: the marketing campaign request (10-example-processes.md, story 1), minus the comms calendar booking.

**Success criteria**:

- The marketing team's campaign-planning spreadsheet is retired.
- Campaign requests generate the right approvals and per-channel tasks automatically.
- Task deadlines respect business days and each business line's holiday calendar.
- A request with an impossible timeline is blocked or clearly flagged at submission, not discovered after the fact.

- **US-11.5** — As a Process Designer, I want to build a flow with branching approvals and business-day deadlines, so that the marketing campaign process no longer needs someone manually recalculating dates in a spreadsheet.
  - A campaign above the budget threshold routes to an additional approval automatically, as described in 05-flows.md.
  - Copywriting, design, and channel setup tasks are created with deadlines counted in business days from the launch date.
- **US-11.6** — As a Business-Line Admin, I want to see Phase 1's pilot results before committing to Phase 2, so that I only expand once the foundation is proven.
  - Given the finance pilot met its success criteria, when Phase 2 begins, then the marketing campaign process is the next one migrated, not a process chosen at random.

## Phase 3 — Comms Calendar

**Scope**:

- Channels and bookable slots with declared capacity.
- The booking lifecycle: tentative hold on submission, confirmed on approval, released on rejection or cancellation.
- Slot booking as a step inside a flow, integrated with the marketing campaign process from Phase 2.

**Success criteria**:

- The shared channel-booking spreadsheet or calendar is retired.
- Every push, social, or pop-up slot is booked through the platform.
- No channel is double-booked past its declared capacity.
- Abandoned or rejected requests release their held slot without manual cleanup.

- **US-11.7** — As an Employee, I want my campaign's channel bookings to be held automatically when I submit the request, so that I don't lose my slot while approval is pending.
  - Submitting a form that requests a slot places a tentative hold, as described in 08-comms-calendar.md.
  - If my request is rejected or abandoned, the hold is released and the slot becomes available again.
- **US-11.8** — As a Business-Line Admin, I want channel capacity enforced automatically, so that the team stops relying on someone remembering which slots are already full.
  - Given a channel's capacity policy is set to hard block, when a slot is full, then no further booking is accepted for it.

## Phase 4 — People Processes

**Scope**:

- Onboarding and offboarding flows (10-example-processes.md, story 3).
- Activation windows so tasks created far in advance don't surface until they're near due.
- Delegation and out-of-office coverage for approvers.
- Escalation chains for overdue approvals.

**Success criteria**:

- HR's onboarding and offboarding checklists are retired.
- Every new hire and every exit runs as a tracked flow with reference numbers and an activity timeline.
- No task surfaces in My Work weeks before it is actionable.
- No approval sits unescalated past its deadline.

- **US-11.9** — As a Business-Line Admin, I want onboarding and offboarding to run as flows with activation windows, so that IT, payroll, and managers only see their task when it's actually time to act.
  - A new-hire flow creates the IT account task, desk and badge task, payroll enrollment task, and welcome task described in 10-example-processes.md, story 3, each surfacing near its own window.
  - An offboarding flow anchors access revocation to the last working day rather than the request date.
- **US-11.10** — As an Approver, I want a delegate to receive my approvals while I'm out of office, so that requests don't stall waiting for someone unavailable.
  - Given I set an out-of-office period, when an approval would be assigned to me, then it is routed to my delegate instead.
  - Escalation still applies if my delegate also misses the deadline.

## Phase 5 — Insight

**Scope**:

- Reporting dashboards covering cycle time, bottleneck steps, SLA breaches, and volumes per process.
- A feedback loop where dashboard findings feed back into flow and deadline redesign.

**Success criteria**:

- The manually built monthly reporting deck or spreadsheet is retired.
- Managers answer "where are things stuck" from the platform's dashboards, without asking anyone to pull numbers by hand.
- Every live process has a visible cycle-time and SLA-breach trend.

- **US-11.11** — As a Business-Line Admin, I want the platform's dashboards to replace my manually built monthly reporting deck, so that I stop assembling KPI figures by hand every period.
  - Given a full reporting period has run through the platform, when I compile my monthly update, then cycle-time, bottleneck, and SLA-breach figures (defined in 09-notifications-and-audit.md, US-09.5) come straight from the dashboard.
  - Managers in my business line can self-serve those same answers without asking me to pull numbers.
- **US-11.12** — As a Platform Admin, I want request volume trends across business lines, so that later rollout decisions are based on data rather than assumption.
  - Given several business lines have completed Phase 1, when I compare their volumes, then I can prioritize which line or process gets Phase 2 capability next.

## Pilot strategy

The finance payment request is the Phase 1 pilot because it is a bounded, high-frequency process with a clear existing spreadsheet to compare against, a small number of approval steps, and a decision that is easy to validate — an approved payment either got executed correctly or it didn't. A narrow, well-understood pilot builds trust in the platform before flow logic, the comms calendar, or people processes are layered on. Later phase-to-phase moves follow the same logic: pick the next process with the clearest spreadsheet to retire and the fewest open dependencies.

## Rollout and adoption principles

- **One process at a time.** Each business line migrates a single process before starting the next, even after Phase 1 is complete platform-wide. Parallel process migrations within one team are avoided.
- **Spreadsheet export is always available.** At every phase, board data can be exported so no team is locked out of their own numbers during the transition, and so a rollback to the old spreadsheet stays possible if a migration stalls.
- **Adoption is won on My Work and email deep links.** People do not need to learn a new tool to participate — approvers and task owners interact through My Work or by clicking straight from an email into the decision or task screen, described in 09-notifications-and-audit.md. Habitual use of these two entry points is the leading indicator that a rollout is succeeding.

## Open questions that gate later phases

These are carried from 00-overview.md and must be resolved before the phase they block can start for a given business line.

- **Expected monthly request volume** — unresolved. Gates Phase 5: without a volume baseline, bottleneck and SLA-breach thresholds cannot be set meaningfully.
- **Who holds the Process Designer role per business line** — unresolved. Gates Phase 2 onward: a business line cannot build branching flows or new forms without someone assigned to design them.
- **Which process pilots second, after finance** — partially resolved: the marketing campaign process is the assumed Phase 2 target, but this should be confirmed against actual Phase 1 results before committing.
- **The working language of each pilot business line** — unresolved per business line, but it gates nothing platform-wide: Arabic and English both ship in Phase 0, and each business line simply picks one when it is created (see 02-tenancy-and-roles.md).

## Out of scope / Later

Timelines between phases are intentionally not dated — each phase starts when the previous one's success criteria are met for the relevant business line, not on a fixed calendar. Running two phases concurrently across different business lines is expected once Phase 1 is proven; running two phases concurrently for the same process is not planned. Capability beyond Phase 5 — such as cross-business-line benchmarking or a process-template library — is not yet scoped and is not part of this roadmap.
