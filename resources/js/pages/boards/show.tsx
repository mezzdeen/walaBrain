import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { ArrowUpDown, FilePlus2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslations } from '@/hooks/use-translations';
import { show } from '@/routes/boards';
import { show as showForm } from '@/routes/forms';
import { show as showNode } from '@/routes/nodes';

type Props = {
    board: { hash_id: string; name: string; space: string };
    fields: {
        hash_id: string;
        name: string;
        type: string;
        options: string[] | null;
    }[];
    groups: { hash_id: string; name: string }[];
    nodes: {
        hash_id: string;
        title: string;
        reference: string | null;
        status: string | null;
        assignee: string | null;
        group: string | null;
        due_date: string | null;
        values: Record<string, unknown>;
    }[];
    sort: string;
    dir: string;
    filters: Record<string, string>;
    forms: { hash_id: string; name: string }[];
};

export default function BoardShow({
    board,
    fields,
    nodes,
    sort,
    dir,
    filters,
    forms,
}: Props) {
    const { t } = useTranslations();

    setLayoutProps({
        breadcrumbs: [{ title: board.name, href: show(board.hash_id) }],
    });

    const visit = (next: Record<string, unknown>) =>
        router.get(
            show(board.hash_id).url,
            { sort, dir, filter: filters, ...next },
            { preserveState: true, preserveScroll: true },
        );

    const sortBy = (key: string) =>
        visit({
            sort: key,
            dir: sort === key && dir === 'asc' ? 'desc' : 'asc',
        });

    const filterBy = (key: string, value: string) =>
        visit({ filter: { ...filters, [key]: value } });

    const selectFields = fields.filter((f) => f.type === 'single_select');

    return (
        <>
            <Head title={board.name} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">{board.name}</h1>
                        <p className="text-sm text-muted-foreground">
                            {board.space}
                        </p>
                    </div>

                    <div className="flex gap-2">
                        {forms.map((form) => (
                            <Button key={form.hash_id} asChild size="sm">
                                <Link
                                    href={showForm(form.hash_id)}
                                    data-test={`open-form-${form.hash_id}`}
                                >
                                    <FilePlus2 className="size-4" />
                                    {form.name}
                                </Link>
                            </Button>
                        ))}
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Input
                        defaultValue={filters.title ?? ''}
                        placeholder={t('boards.table.search')}
                        className="max-w-56"
                        data-test="filter-title"
                        onBlur={(event) =>
                            filterBy('title', event.currentTarget.value)
                        }
                        onKeyDown={(event) =>
                            event.key === 'Enter' &&
                            filterBy('title', event.currentTarget.value)
                        }
                    />

                    {selectFields.map((field) => (
                        <Select
                            key={field.hash_id}
                            value={filters[field.hash_id] ?? 'all'}
                            onValueChange={(value) =>
                                filterBy(
                                    field.hash_id,
                                    value === 'all' ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger
                                className="w-40"
                                data-test={`filter-${field.hash_id}`}
                            >
                                <SelectValue placeholder={field.name} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    {field.name}: {t('boards.table.all')}
                                </SelectItem>
                                {(field.options ?? []).map((option) => (
                                    <SelectItem key={option} value={option}>
                                        {option}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    ))}
                </div>

                <Card>
                    <CardContent className="overflow-x-auto p-0">
                        <table
                            className="w-full text-sm"
                            data-test="board-table"
                        >
                            <thead>
                                <tr className="border-b text-start">
                                    {[
                                        {
                                            key: 'reference',
                                            label: t('boards.table.reference'),
                                        },
                                        {
                                            key: 'title',
                                            label: t('boards.table.title'),
                                        },
                                        ...fields.map((field) => ({
                                            key: field.hash_id,
                                            label: field.name,
                                        })),
                                        {
                                            key: 'status',
                                            label: t('boards.table.status'),
                                        },
                                        {
                                            key: 'due_date',
                                            label: t('boards.table.due'),
                                        },
                                    ].map((column) => (
                                        <th
                                            key={column.key}
                                            className="px-3 py-2 text-start font-medium"
                                        >
                                            <button
                                                type="button"
                                                className="inline-flex items-center gap-1 hover:text-foreground"
                                                onClick={() =>
                                                    sortBy(column.key)
                                                }
                                                data-test={`sort-${column.key}`}
                                            >
                                                {column.label}
                                                <ArrowUpDown className="size-3 opacity-50" />
                                            </button>
                                        </th>
                                    ))}
                                    <th className="px-3 py-2 text-start font-medium">
                                        {t('boards.table.assignee')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {nodes.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={fields.length + 5}
                                            className="px-3 py-8 text-center text-muted-foreground"
                                        >
                                            {t('boards.table.empty')}
                                        </td>
                                    </tr>
                                )}
                                {nodes.map((node) => (
                                    <tr
                                        key={node.hash_id}
                                        className="border-b transition-colors last:border-0 hover:bg-accent/40"
                                        data-test={`row-${node.hash_id}`}
                                    >
                                        <td className="px-3 py-2">
                                            <Link
                                                href={showNode(node.hash_id)}
                                                className="font-medium underline-offset-2 hover:underline"
                                            >
                                                {node.reference ?? '—'}
                                            </Link>
                                        </td>
                                        <td className="px-3 py-2">
                                            <Link
                                                href={showNode(node.hash_id)}
                                                className="hover:underline"
                                            >
                                                {node.title}
                                            </Link>
                                        </td>
                                        {fields.map((field) => (
                                            <td
                                                key={field.hash_id}
                                                className="px-3 py-2"
                                            >
                                                {node.values[field.hash_id] ==
                                                null
                                                    ? '—'
                                                    : String(
                                                          node.values[
                                                              field.hash_id
                                                          ],
                                                      )}
                                            </td>
                                        ))}
                                        <td className="px-3 py-2">
                                            {node.status ? (
                                                <Badge variant="secondary">
                                                    {t(
                                                        `flows.status.${node.status}`,
                                                    )}
                                                </Badge>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className="px-3 py-2">
                                            {node.due_date ?? '—'}
                                        </td>
                                        <td className="px-3 py-2">
                                            {node.assignee ?? '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
