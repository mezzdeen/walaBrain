import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { FieldInput } from '@/components/field-input';
import type { FieldDefinition } from '@/components/field-input';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslations } from '@/hooks/use-translations';
import { resubmit } from '@/routes/nodes';

type NodeValue = FieldDefinition & { value: unknown };

type Props = {
    node: {
        hash_id: string;
        title: string;
        reference: string | null;
        status: string | null;
        board: string;
        assignee: string | null;
        submitter: string | null;
        due_date: string | null;
        created_at: string | null;
    };
    values: NodeValue[];
    can_resubmit: boolean;
    timeline: {
        type: string;
        payload: Record<string, unknown> | null;
        actor: string | null;
        at: string | null;
    }[];
};

const statusVariant = (
    status: string | null,
): 'default' | 'secondary' | 'destructive' =>
    status === 'rejected'
        ? 'destructive'
        : status === 'approved'
          ? 'default'
          : 'secondary';

export default function NodeShow({
    node,
    values,
    can_resubmit,
    timeline,
}: Props) {
    const { t } = useTranslations();
    const title = node.reference ?? node.title;

    setLayoutProps({ breadcrumbs: [{ title, href: '#' }] });

    return (
        <>
            <Head title={title} />

            <div className="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center gap-3">
                    <h1 className="text-xl font-semibold">{title}</h1>
                    {node.status && (
                        <Badge
                            variant={statusVariant(node.status)}
                            data-test="node-status"
                        >
                            {t(`flows.status.${node.status}`)}
                        </Badge>
                    )}
                </div>
                <p className="-mt-4 text-sm text-muted-foreground">
                    {node.title} · {node.board}
                    {node.submitter ? ` · ${node.submitter}` : ''}
                </p>

                {can_resubmit ? (
                    <Card data-test="resubmit-card">
                        <CardContent className="pt-6">
                            <h2 className="mb-1 text-sm font-semibold">
                                {t('flows.resubmit.title')}
                            </h2>
                            <p className="mb-4 text-sm text-muted-foreground">
                                {t('flows.resubmit.description')}
                            </p>
                            <Form
                                {...resubmit.form(node.hash_id)}
                                className="flex flex-col gap-5"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        {values.map((field) => (
                                            <FieldInput
                                                key={field.hash_id}
                                                field={field}
                                                defaultValue={
                                                    field.value === null
                                                        ? undefined
                                                        : String(field.value)
                                                }
                                                error={
                                                    errors[
                                                        `values.${field.hash_id}`
                                                    ]
                                                }
                                            />
                                        ))}
                                        <InputError message={errors.form} />
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            data-test="resubmit-submit"
                                        >
                                            {t('flows.resubmit.submit')}
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                ) : (
                    values.length > 0 && (
                        <Card>
                            <CardContent className="grid gap-3 pt-6 sm:grid-cols-2">
                                {values.map((field) => (
                                    <div key={field.hash_id}>
                                        <div className="text-xs text-muted-foreground">
                                            {field.name}
                                        </div>
                                        <div className="text-sm font-medium">
                                            {field.value === null
                                                ? '—'
                                                : String(field.value)}
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )
                )}

                <Card>
                    <CardContent className="flex flex-col gap-0 pt-6">
                        <h2 className="mb-3 text-sm font-semibold">
                            {t('boards.nodes.timeline')}
                        </h2>
                        <ol
                            className="relative flex flex-col gap-4 border-s ps-4"
                            data-test="timeline"
                        >
                            {timeline.map((entry, index) => (
                                <li key={index} className="text-sm">
                                    <span className="absolute -start-1 mt-1.5 size-2 rounded-full bg-border" />
                                    <div className="font-medium">
                                        {t(
                                            `boards.nodes.events.${entry.type}`,
                                        ) ===
                                        `boards.nodes.events.${entry.type}`
                                            ? entry.type
                                            : t(
                                                  `boards.nodes.events.${entry.type}`,
                                              )}
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        {entry.actor ??
                                            t('boards.nodes.by_system')}
                                        {' · '}
                                        {entry.at}
                                    </div>
                                    {entry.payload?.comment != null && (
                                        <p className="mt-1 text-muted-foreground">
                                            {String(entry.payload.comment)}
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ol>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
