import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { CalendarClock, Check, Plus, Stamp } from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslations } from '@/hooks/use-translations';
import { show as showApproval } from '@/routes/approvals';
import { complete, index, store } from '@/routes/my-work';

type Task = {
    hash_id: string;
    title: string;
    description: string | null;
    due_date: string | null;
    is_overdue: boolean;
    board: string;
};

type Person = {
    hash_id: string;
    name: string;
};

type PendingApproval = {
    hash_id: string;
    reference: string | null;
    title: string;
    requested_at: string | null;
};

type Props = {
    tasks: Task[];
    assignable: Person[];
    approvals?: PendingApproval[];
};

export default function MyWork({ tasks, assignable, approvals = [] }: Props) {
    const { t, locale } = useTranslations();

    setLayoutProps({
        breadcrumbs: [{ title: t('boards.tasks.title'), href: index() }],
    });

    const formatDate = (value: string) =>
        new Date(value).toLocaleDateString(locale, {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });

    return (
        <>
            <Head title={t('boards.tasks.title')} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold">
                        {t('boards.tasks.title')}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {t('boards.tasks.description')}
                    </p>
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <Form
                            {...store.form()}
                            options={{ preserveScroll: true }}
                            resetOnSuccess
                            className="grid gap-4 sm:grid-cols-[1fr_auto_auto_auto] sm:items-end"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="title">
                                            {t('boards.tasks.add_title')}
                                        </Label>
                                        <Input
                                            id="title"
                                            name="title"
                                            data-test="task-title"
                                            required
                                            autoComplete="off"
                                        />
                                        <InputError message={errors.title} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="due_date">
                                            {t('boards.tasks.add_due')}
                                        </Label>
                                        <Input
                                            id="due_date"
                                            name="due_date"
                                            type="date"
                                            data-test="task-due-date"
                                        />
                                        <InputError message={errors.due_date} />
                                    </div>

                                    {assignable.length > 1 && (
                                        <div className="grid gap-2">
                                            <Label htmlFor="assignee">
                                                {t('boards.tasks.add_assignee')}
                                            </Label>
                                            <Select name="assignee">
                                                <SelectTrigger
                                                    id="assignee"
                                                    data-test="task-assignee"
                                                    className="min-w-40"
                                                >
                                                    <SelectValue
                                                        placeholder={t(
                                                            'boards.tasks.add_yourself',
                                                        )}
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {assignable.map(
                                                        (person) => (
                                                            <SelectItem
                                                                key={
                                                                    person.hash_id
                                                                }
                                                                value={
                                                                    person.hash_id
                                                                }
                                                            >
                                                                {person.name}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={errors.assignee}
                                            />
                                        </div>
                                    )}

                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        data-test="task-submit"
                                    >
                                        <Plus className="size-4" />
                                        {t('boards.tasks.add_submit')}
                                    </Button>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>

                {approvals.length > 0 && (
                    <section
                        className="flex flex-col gap-2"
                        data-test="approvals-section"
                    >
                        <h2 className="text-sm font-semibold">
                            {t('flows.approvals.title')}
                        </h2>
                        <ul className="grid gap-2">
                            {approvals.map((approval) => (
                                <li key={approval.hash_id}>
                                    <Card>
                                        <CardContent className="flex items-center justify-between gap-4 py-4">
                                            <div className="min-w-0">
                                                <div className="flex items-center gap-2">
                                                    <Stamp className="size-4 text-muted-foreground" />
                                                    <span className="truncate font-medium">
                                                        {approval.reference ??
                                                            approval.title}
                                                    </span>
                                                </div>
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    {approval.title}
                                                    {approval.requested_at
                                                        ? ` · ${t('flows.approvals.requested', { date: approval.requested_at })}`
                                                        : ''}
                                                </p>
                                            </div>
                                            <Button asChild size="sm">
                                                <Link
                                                    href={showApproval(
                                                        approval.hash_id,
                                                    )}
                                                    data-test={`approval-${approval.hash_id}`}
                                                >
                                                    {t(
                                                        'flows.approvals.decide',
                                                    )}
                                                </Link>
                                            </Button>
                                        </CardContent>
                                    </Card>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                {tasks.length === 0 ? (
                    <Card data-test="my-work-empty">
                        <CardContent className="flex flex-col items-center gap-1 py-12 text-center">
                            <p className="font-medium">
                                {t('boards.tasks.empty')}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {t('boards.tasks.empty_hint')}
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <ul className="grid gap-2" data-test="my-work-list">
                        {tasks.map((task) => (
                            <li key={task.hash_id}>
                                <Card>
                                    <CardContent className="flex items-center justify-between gap-4 py-4">
                                        <div className="min-w-0">
                                            <div className="flex items-center gap-2">
                                                <span
                                                    className="truncate font-medium"
                                                    data-test="task-title-text"
                                                >
                                                    {task.title}
                                                </span>
                                                {task.is_overdue && (
                                                    <Badge
                                                        variant="destructive"
                                                        data-test="task-overdue"
                                                    >
                                                        {t(
                                                            'boards.tasks.overdue',
                                                        )}
                                                    </Badge>
                                                )}
                                            </div>

                                            <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                                                <span className="inline-flex items-center gap-1">
                                                    <CalendarClock className="size-3.5" />
                                                    {task.due_date
                                                        ? t(
                                                              'boards.tasks.due',
                                                              {
                                                                  date: formatDate(
                                                                      task.due_date,
                                                                  ),
                                                              },
                                                          )
                                                        : t(
                                                              'boards.tasks.no_due_date',
                                                          )}
                                                </span>
                                                <span>
                                                    {t(
                                                        'boards.tasks.on_board',
                                                        {
                                                            board: task.board,
                                                        },
                                                    )}
                                                </span>
                                            </div>

                                            {task.description && (
                                                <p className="mt-1 truncate text-sm text-muted-foreground">
                                                    {task.description}
                                                </p>
                                            )}
                                        </div>

                                        <Form
                                            {...complete.form(task.hash_id)}
                                            options={{ preserveScroll: true }}
                                        >
                                            {({ processing }) => (
                                                <Button
                                                    type="submit"
                                                    variant="outline"
                                                    size="sm"
                                                    disabled={processing}
                                                    data-test={`task-complete-${task.hash_id}`}
                                                >
                                                    <Check className="size-4" />
                                                    {t('boards.tasks.complete')}
                                                </Button>
                                            )}
                                        </Form>
                                    </CardContent>
                                </Card>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
