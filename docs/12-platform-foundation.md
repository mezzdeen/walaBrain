# Platform Foundation & Conventions

## Purpose

The twelve documents before this one describe what the platform does. This one describes what already exists to build it on, and the conventions every new part of it follows. It exists so that nobody re-specifies or rebuilds a capability that is already working, and so that the five pillars are built the same way as each other rather than three different ways.

Read this before building anything. It carries no user stories — it is a reference for builders, not a description of what a persona does. Where a term here differs from the business vocabulary in 01-glossary.md, the mapping is given below.

## What Is Already Built

The platform is not starting from an empty application. A foundation module — `Core` — already provides, tested, the capabilities the rest of these documents assume without describing:

| Capability | Covers | Documented in |
|---|---|---|
| **Tenancy isolation** | Every record belongs to one business line; reads are filtered and writes are stamped automatically | 02-tenancy-and-roles.md, rule 1 |
| **Membership and switching** | One account, membership of several business lines, one active at a time, deliberate switching | 02-tenancy-and-roles.md |
| **Roles and capabilities** | Custom roles per business line, a capability matrix, and the rule that nobody grants what they don't hold | 02-tenancy-and-roles.md, rule 2 |
| **Invitations** | Invite by email, single-use token, accept-to-join, resend and revoke | 02-tenancy-and-roles.md |
| **Authentication** | Passkeys, email and password, two-factor with recovery codes, email verification, password reset | 02-tenancy-and-roles.md |
| **Administration platform** | A separate area with its own accounts and its own sign-in, for managing business lines platform-wide | 02-tenancy-and-roles.md, Platform Admin |
| **Arabic and English** | Both interface languages, per-person choice, right-to-left layout, translated notifications | 00-overview.md, Working Language |
| **Platform settings** | Whether self-service registration is open, and which sign-in methods are enabled | 02-tenancy-and-roles.md |
| **Opaque URL codes** | Records are addressed in links by a short code, never by a sequential database id | See Two Kinds of Identifier below |
| **Recoverability** | People, business lines, and records are soft-deleted and can be restored | — |

Everything in the five pillars — boards, nodes, fields, forms, flows, approvals, tasks, the comms calendar, the audit timeline, and reporting — is new work built on top of this.

## Vocabulary Mapping

The business vocabulary and the code deliberately differ in one place, and only one:

| These documents say | The code calls it | Note |
|---|---|---|
| Business line | `Organization` | "Business line" is an interface label, so it is a translated string and can read differently in Arabic and English. No code rename is planned. |
| Business-Line Admin | The `Owner` role | The built-in role every business line starts with. |
| Platform Admin | `Admin`, on the `super` guard | A separate account type with a separate sign-in, not a user with extra permissions. |
| Capability | Permission | A named string like `roles.update`, bundled into roles. |
| Working language | The business line's `locale` | Distinct from a person's own interface `locale`. |

Everywhere else the two agree, and new code should keep it that way: a board is a `Board`, a node is a `Node`, a run is a `Run`.

## Module Structure

The application is built from **modules**, each a directory under `app/Modules` holding everything it owns: its models, migrations, factories, seeders, routes, controllers, policies, translations, and tests. A module is registered simply by existing — dropping the directory in adds it, deleting the directory removes it, and no shared file has to be edited either way.

`Core` is the foundation module described above. The five pillars are expected to become modules alongside it rather than accumulating inside `Core`, so each can be built, tested, and reasoned about separately.

Each module carries its own translations in both languages, and its own test suite. A module's tests live with it, not in a central directory, so deleting a module takes its tests with it.

## Conventions Every Module Follows

**Tenancy is declarative, not manual.** A model that belongs to a business line says so once, and from then on every query is filtered to the active business line and every new record is stamped with it. A write attempted with no business line active fails loudly rather than creating an orphaned record that belongs to nobody and is visible to no one. No controller should ever be filtering by business line by hand.

**Background work must name its business line.** Middleware sets the active business line for web requests only. Anything running outside a request — a queued job resuming a sleeping flow run, a scheduled command sending reminders or expiring calendar holds, a console command — has no active business line, so scoped reads return nothing and scoped writes throw. Such work must state which business line it is acting for. This matters more here than in most applications, because durable flow runs (05-flows.md) mean the majority of the platform's work happens outside a request.

**Authorization is enforced on the server.** Hiding a button in the interface is a courtesy; the route must independently refuse the request. Every capability check has a policy behind it.

**Both languages, always.** Any interface text a module introduces is added to both the Arabic and English translation files at the same time. A key that exists in only one language is a bug, not a to-do.

**Every change is tested.** New behaviour comes with a test that proves it, run before the change is considered done.

## Two Kinds of Identifier

These are easy to confuse and serve unrelated purposes. Both apply to a node created by a form:

| | **Reference number** | **Public link code** |
|---|---|---|
| Example | `MKT-2026-0042` | `k3Rf9` |
| Purpose | The business identity of a request — what people say to each other and search for | Addressing a record in a URL |
| Who sees it | Everyone, on the node and in every notification | Only in the address bar |
| Shape | Process prefix, year, and a sequence that restarts each year | A short opaque code |
| Why | A request needs a name a person can quote in a conversation | A sequential id in a link would disclose how many records exist and in what order they were created |
| Defined in | 04-forms.md, rule 12 | Already built (see below) |

The reference number is new work, specified in 04-forms.md. The public link code already exists and applies automatically to any record that opts into it. The two never substitute for each other: a reference number does not appear in a URL, and a link code is never shown as a request's identity.

One operational caution: link codes are derived from a configured alphabet rather than stored. Changing that alphabet after deployment silently invalidates every link and bookmark ever issued. It is set once per deployment and treated as a constant.

## Storing a Record's Own Fields

Boards let a Process Designer invent fields, so a node cannot have a fixed set of columns. The split is:

- **Real columns** for anything queried *across* boards — the owning business line, board, group, assignee, due date, and status. My Work sorts every node by due date across every board in a business line, and the flow engine finds overdue work the same way; neither can afford to be a JSON extraction.
- **A `jsonb` column** for the board's own user-defined fields, keyed by field.

Postgres indexes and sorts `jsonb` perfectly well, but the indexes that make it fast are written per field, and a field a Process Designer has not invented yet cannot be indexed in advance. That is a real ceiling with a known remedy rather than a free lunch, and it is a long way off at the volumes in 09-notifications-and-audit.md.

What does need care from the first migration is the storage contract per field type — an amount stored so it always casts to a number, a date stored so it always sorts chronologically. A single value written with a thousands separator breaks sorting on that column for everyone, and it is not a mistake that announces itself.

## What Core Gained in Phase 0

Six capabilities, all shared foundation rather than any pillar's own. Each is described in full where it belongs — this is a map, not a second description:

| Capability | What to know about it | See |
|---|---|---|
| **Append-only activity timeline** | Append-only by construction: the model refuses updates and deletes, and a database trigger refuses them again, because a model is stepped around the moment anyone writes a query builder statement or opens a psql session. Corrections are new entries. | 09 |
| **Spaces and space membership** | Administering a space is a capability held through a role; reaching one is membership, at view or edit. Every organization is created with one default space, implicit for all its members. | 02, 03 |
| **Working language** | The language a business line's people author in, as distinct from the language each person reads the interface in. | 02 |
| **Managers and reporting lines** | Optional, and held per membership rather than per person. Grants assigning a report work and deciding their approvals, and nothing more. | 02, 06, 07 |
| **Tenant-safe background work** | A job carries its organization into the worker automatically, and a worker clears it between jobs so nothing is inherited by whatever runs next. | 05, 08, 09 |
| **In-app notification centre** | Confined to the organization someone is working in, plus the notifications about their own account, which belong to no organization at all. | 09 |

### Still needed, and where it went

- **File attachments** — deferred to Phase 1, alongside the field types that need them. Nothing in the foundation depends on storage.
- **Capabilities for boards, forms and flows** — each is added with the feature it authorizes. A permission that authorizes nothing still renders as a checkbox in the role matrix and reads as though it does something, so only `spaces.manage` exists so far.
- **Business-day calculation and the holiday calendar** — Phase 2, where the counting rules in 07-tasks-and-deadlines.md first matter.
- **Screens** for spaces, reporting lines and the notification centre. All three have their models, policies and tests; none has an interface yet, so the user stories that describe administering them are not satisfied until it exists.

## Out of Scope / Later

- Deployment, hosting, environments, and backups are not covered here.
- Data migration from the spreadsheets each phase retires is a per-phase concern; see the rollout principles in 11-roadmap.md.
- Performance and scale targets are not set until the expected request volume open question in 00-overview.md is resolved.
- Two packages are installed but not yet used by any feature: object storage, which the File field type will need, and an AI SDK, which nothing in these documents currently calls for.
