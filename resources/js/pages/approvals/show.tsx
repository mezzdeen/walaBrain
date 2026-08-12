import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/use-translations';
import { store } from '@/routes/approvals';

type Props = {
    approval: { hash_id: string; status: string; is_pending: boolean };
    node: {
        hash_id: string;
        title: string;
        reference: string | null;
        status: string | null;
        submitter: string | null;
        submitted_at: string | null;
    };
    values: { name: string; type: string; value: unknown }[];
    history: {
        approver: string;
        status: string;
        comment: string | null;
        decided_at: string | null;
    }[];
};

export default function ApprovalShow({
    approval,
    node,
    values,
    history,
}: Props) {
    const { t } = useTranslations();
    const [decision, setDecision] = useState<string>('approved');

    setLayoutProps({
        breadcrumbs: [{ title: node.reference ?? node.title, href: '#' }],
    });

    return (
        <>
            <Head title={node.reference ?? node.title} />

            <div className="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold">
                        {node.reference} — {node.title}
                    </h1>
                    {node.submitter && (
                        <p className="text-sm text-muted-foreground">
                            {t('flows.decision.submitted_by', {
                                name: node.submitter,
                                date: node.submitted_at ?? '',
                            })}
                        </p>
                    )}
                </div>

                <Card>
                    <CardContent className="grid gap-3 pt-6 sm:grid-cols-2">
                        {values.map((value) => (
                            <div key={value.name}>
                                <div className="text-xs text-muted-foreground">
                                    {value.name}
                                </div>
                                <div
                                    className="text-sm font-medium"
                                    data-test={`value-${value.name}`}
                                >
                                    {value.value === null
                                        ? '—'
                                        : String(value.value)}
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                {history.length > 0 && (
                    <Card>
                        <CardContent className="flex flex-col gap-3 pt-6">
                            <h2 className="text-sm font-semibold">
                                {t('flows.decision.history')}
                            </h2>
                            {history.map((entry, index) => (
                                <div
                                    key={index}
                                    className="flex flex-col gap-1 rounded-md border p-3 text-sm"
                                >
                                    <div className="flex items-center gap-2">
                                        <span className="font-medium">
                                            {entry.approver}
                                        </span>
                                        <Badge variant="secondary">
                                            {t(`flows.status.${entry.status}`)}
                                        </Badge>
                                        <span className="text-xs text-muted-foreground">
                                            {entry.decided_at}
                                        </span>
                                    </div>
                                    {entry.comment && (
                                        <p className="text-muted-foreground">
                                            {entry.comment}
                                        </p>
                                    )}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {approval.is_pending ? (
                    <Card>
                        <CardContent className="pt-6">
                            <Form
                                {...store.form(approval.hash_id)}
                                className="flex flex-col gap-4"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <h2 className="text-sm font-semibold">
                                            {t('flows.decision.title')}
                                        </h2>

                                        <input
                                            type="hidden"
                                            name="decision"
                                            value={decision}
                                        />

                                        <div className="grid gap-2">
                                            <Label htmlFor="comment">
                                                {t('flows.decision.comment')}
                                            </Label>
                                            <textarea
                                                id="comment"
                                                name="comment"
                                                data-test="decision-comment"
                                                className="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                {t(
                                                    'flows.decision.comment_required',
                                                )}
                                            </p>
                                            <InputError
                                                message={errors.comment}
                                            />
                                        </div>

                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                onClick={() =>
                                                    setDecision('approved')
                                                }
                                                data-test="decision-approve"
                                            >
                                                {t('flows.decision.approve')}
                                            </Button>
                                            <Button
                                                type="submit"
                                                variant="outline"
                                                disabled={processing}
                                                onClick={() =>
                                                    setDecision(
                                                        'changes_requested',
                                                    )
                                                }
                                                data-test="decision-changes"
                                            >
                                                {t(
                                                    'flows.decision.request_changes',
                                                )}
                                            </Button>
                                            <Button
                                                type="submit"
                                                variant="destructive"
                                                disabled={processing}
                                                onClick={() =>
                                                    setDecision('rejected')
                                                }
                                                data-test="decision-reject"
                                            >
                                                {t('flows.decision.reject')}
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        {t('flows.decision.already_decided')}
                    </p>
                )}
            </div>
        </>
    );
}
