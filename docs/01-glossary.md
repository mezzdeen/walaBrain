# Glossary

## Purpose

This document defines the platform's core terms exactly once, using the language every other document in this set relies on, so a board, a node, a flow, and a run mean the same thing wherever they appear. It is a reference, not a feature description: each term below gets a short business definition, a concrete example, and a pointer to the document that owns its full mechanics — start there for how a term actually works, not here.

## How These Terms Fit Together

The eighteen terms below fall into three layers that build on each other, plus two terms that cut across all of them.

- **Structure**: a Business line contains Spaces, a Space contains Boards, a Board contains Groups, and a Group contains Nodes. A Business line also fixes the Working language its content is authored in.
- **Automation**: a Form creates a Node; a Flow reacts to it with a sequence of Steps, running as one Run; Approval and Task are the two Step types that do the actual work.
- **Communications calendar**: a Channel offers Slots; a Node's Booking claims one, moving from tentative to confirmed or released.
- **Cross-cutting**: My Work surfaces every Approval and Task currently waiting on one person; Version tracks how a Form or Flow's definition changes over time without disturbing work already in progress.

## Tenancy & Structure

These seven terms describe where work lives before any automation touches it.

**Business line** — The platform's tenancy boundary: an isolated part of the company with its own users, spaces, boards, holiday calendar, and working language. Nothing — users, data, or settings — is shared across business lines. One person can be a member of more than one, but works in exactly one at a time. Called `Organization` in the codebase; "business line" is the label the interface shows.
*Example:* Finance, Marketing, and HR each run as separate business lines with their own people and data.
*Business rule:* 1 — tenancy isolation.
*Related:* Space, Working language.
*See:* 02-tenancy-and-roles.md.

**Space** — A container inside a business line that groups a related set of boards, typically one team or process area. Access is granted by **membership**: a person is added to a space and given view or edit access there, independently of the roles they hold at the business-line level.
*Example:* A Marketing business line might hold a Campaigns space and a separate Events space.
*Related:* Business line, Board.
*See:* 02-tenancy-and-roles.md.

**Working language** — The single language a business line authors its content in: field labels, help text, select options, and the names of spaces, boards, groups, custom roles, and channels. Fixed when the business line is created. Distinct from the interface language, which each person chooses for themselves and which flips layout direction with it.
*Example:* A business line whose working language is Arabic shows Arabic field labels to every member, including one reading the interface in English.
*Related:* Business line, Field, Form.
*See:* 02-tenancy-and-roles.md.

**Board** — Lives in a space and owns the field schema, groups, forms, and flows for one process. Every node on a board shares that board's fields, however the node was created.
*Example:* The Marketing board hosts the campaign request form, its groups, and its approval flow.
*Related:* Space, Group, Field, Form, Flow.
*See:* 03-boards-and-nodes.md.

**Group** — A visual partition of a board's nodes, most often one group per stage of work. Grouping only changes how nodes are displayed; every node in every group still shares the board's field schema.
*Example:* A campaign board grouping nodes into Draft, In Review, and Live.
*Related:* Board, Node.
*See:* 03-boards-and-nodes.md.

**Node** — The universal work item: a row on a board that can represent a request, a task, a lead, a booking, or any other tracked record, carrying the board's fields, a reference number, and an append-only activity timeline.
*Example:* The campaign request MKT-2026-0042.
*Business rule:* 12 — reference numbers.
*Related:* Board, Field, Run.
*See:* 03-boards-and-nodes.md.

**Field** — A typed column defined once on a board; boards and forms share the same ten field types, so a field behaves identically wherever it appears.
*Example:* Launch date is a Date field; Budget is a Money field.
*Business rule:* 3 — the ten field types.
*Related:* Board, Form.
*See:* 03-boards-and-nodes.md for the canonical field types; 04-forms.md for how forms use them.

## Process Automation

These six terms describe how a submission turns into decisions and work.

**Form** — An intake surface that creates a node on a board when submitted; submitting a form can also trigger a flow.
*Example:* The Payment Request form creates a node on the Finance board and starts its approval flow.
*Related:* Board, Node, Flow, Version.
*See:* 04-forms.md.

**Flow** — A durable workflow attached to a board: one trigger plus a sequence of steps that can pause for days or weeks waiting on a person or a date.
*Example:* The campaign flow routes a request through a manager approval, then generates per-channel tasks.
*Related:* Board, Step, Run, Version.
*See:* 05-flows.md.

**Step** — A single building block inside a flow — Trigger, Condition, Approval, Task, Wait, Notify, or Book Slot. Never call a step a node; node is reserved for board items.
*Example:* A Wait step that pauses a run until 3 business days after submission.
*Business rule:* 5 — flow step vocabulary.
*Related:* Flow, Approval, Task.
*See:* 05-flows.md.

**Run** — One execution of a flow for one node, moving through the flow's steps until it completes, is rejected, or is cancelled.
*Example:* Submitting MKT-2026-0042 starts one run of the campaign flow.
*Related:* Flow, Node, Step.
*See:* 05-flows.md.

**Approval** — A step type that asks one or more people to approve, reject, or request changes before a run continues.
*Example:* The marketing director's sign-off on a campaign whose budget exceeds the spend threshold.
*Business rule:* 6 — approval modes and decisions.
*Related:* Step, Task, Run.
*See:* 06-approvals.md.

**Task** — A step type that creates a work assignment: it appears as a node on its board and, at the same time, in the assignee's My Work.
*Example:* A copywriting task due 10 business days before launch.
*Business rule:* 7 — assignment rules; 8 — deadline rules.
*Related:* Step, My Work, Node.
*See:* 07-tasks-and-deadlines.md.

## Communications Calendar

These three terms describe how channel capacity is booked and released.

**Channel** — A bookable communication surface in the comms calendar, made up of slots with declared capacity.
*Example:* Push, Social, and Pop-up are the v1 channels.
*Related:* Slot, Booking.
*See:* 08-comms-calendar.md.

**Slot** — A single bookable date/time window on a channel with a fixed capacity.
*Example:* A Push channel slot for July 30 that holds up to four bookings.
*Business rule:* 11 — capacity enforcement.
*Related:* Channel, Booking.
*See:* 08-comms-calendar.md.

**Booking** — A node's claim on a slot: a tentative hold on submission, confirmed on approval, released on rejection, cancellation, or expiry.
*Example:* A campaign's push-notification hold, confirmed once the marketing director approves.
*Business rule:* 11 — hold, confirm, release.
*Related:* Slot, Node, Approval.
*See:* 08-comms-calendar.md.

## Personal Views & Versioning

These two terms cut across every board and process above.

**My Work** — Every person's personal, cross-board view of every task and approval currently waiting on them, across all the spaces in their business line, sorted by due date.
*Example:* A finance manager's My Work lists every payment approval waiting on them, regardless of which board it came from.
*Business rule:* 13 — My Work as the primary daily screen.
*Related:* Task, Approval.
*See:* 03-boards-and-nodes.md.

**Version** — A published, immutable snapshot of a form or flow definition. Editing a published version creates a new draft; a node already in progress finishes on the version it started with, even after a newer version is published.
*Example:* Publishing campaign flow version 2 does not change runs already in progress on version 1.
*Business rule:* 4 — versioning.
*Related:* Form, Flow.
*See:* 04-forms.md and 05-flows.md.

## Quick Reference by Document

Use this table to jump straight to a term's full mechanics.

| Term | Owning document |
|---|---|
| Business line | 02-tenancy-and-roles.md |
| Space | 02-tenancy-and-roles.md |
| Board | 03-boards-and-nodes.md |
| Group | 03-boards-and-nodes.md |
| Node | 03-boards-and-nodes.md |
| Field | 03-boards-and-nodes.md |
| Working language | 02-tenancy-and-roles.md |
| Form | 04-forms.md |
| Flow | 05-flows.md |
| Step | 05-flows.md |
| Run | 05-flows.md |
| Approval | 06-approvals.md |
| Task | 07-tasks-and-deadlines.md |
| Channel | 08-comms-calendar.md |
| Slot | 08-comms-calendar.md |
| Booking | 08-comms-calendar.md |
| My Work | 03-boards-and-nodes.md |
| Version | 04-forms.md, 05-flows.md |

## Out of Scope / Later

- Personas (Platform Admin, Business-Line Admin, Process Designer, Employee, Approver) are defined in 02-tenancy-and-roles.md, not here — this glossary covers platform terminology, not roles.
- Reporting vocabulary (cycle time, bottleneck step, SLA breach) is defined where it's used, in 09-notifications-and-audit.md, since it describes derived KPIs rather than a core platform concept.
- Terms introduced by a later roadmap phase — delegation, out-of-office coverage, escalation contact beyond the single configured one — are not yet canonical; they will be added here once a phase in 11-roadmap.md ships them.
- Implementation vocabulary — module, permission team, global scope, hash id — is not defined here; see 12-platform-foundation.md for the terms the codebase uses and how they map onto the ones above.
