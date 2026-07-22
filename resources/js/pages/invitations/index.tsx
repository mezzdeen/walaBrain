import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { Send, Trash2 } from 'lucide-react';
import { EmailCombobox } from '@/components/email-combobox';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/hooks/use-translations';
import { index } from '@/routes/invitations';
import { destroy, resend, store } from '@/routes/invitations/manage';
import { search } from '@/routes/members';

type RoleOption = {
    value: string;
    label: string;
};

type PendingInvitation = {
    hash_id: string;
    email: string;
    role: string;
    expires_at: string;
};

type Props = {
    roles: RoleOption[];
    invitations: PendingInvitation[];
};

export default function MemberInvitations({ roles, invitations }: Props) {
    const { t } = useTranslations();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('core.invitations.manage.title'),
                href: index(),
            },
        ],
    });

    return (
        <>
            <Head title={t('core.invitations.manage.title')} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold">
                        {t('core.invitations.manage.title')}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {t('core.invitations.manage.description')}
                    </p>
                </div>

                <Card>
                    <CardContent>
                        <Form
                            {...store.form()}
                            resetOnSuccess
                            className="flex flex-col gap-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <EmailCombobox
                                        name="email"
                                        error={errors.email}
                                        inputId="invite_email"
                                        searchUrl={(q) =>
                                            search.url({ query: { q } })
                                        }
                                        copy={{
                                            label: t(
                                                'core.invitations.manage.email_label',
                                            ),
                                            placeholder: t(
                                                'core.invitations.manage.email_placeholder',
                                            ),
                                            searching: t(
                                                'core.invitations.manage.searching',
                                            ),
                                            selectedNote: t(
                                                'core.invitations.manage.will_be_added',
                                            ),
                                            noResults: t(
                                                'core.invitations.manage.no_results',
                                            ),
                                            willBeInvited: t(
                                                'core.invitations.manage.will_be_invited',
                                            ),
                                            memberBadge: t(
                                                'core.invitations.manage.already_member_badge',
                                            ),
                                            clear: t(
                                                'core.invitations.manage.clear',
                                            ),
                                        }}
                                    />

                                    <div className="grid gap-2">
                                        <Label htmlFor="invite_role">
                                            {t(
                                                'core.invitations.manage.role_label',
                                            )}
                                        </Label>
                                        <Select name="role">
                                            <SelectTrigger
                                                id="invite_role"
                                                className="w-full"
                                            >
                                                <SelectValue
                                                    placeholder={t(
                                                        'core.invitations.manage.role_placeholder',
                                                    )}
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {roles.map((role) => (
                                                    <SelectItem
                                                        key={role.value}
                                                        value={role.value}
                                                    >
                                                        {role.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.role} />
                                    </div>

                                    <div>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing ? (
                                                <Spinner />
                                            ) : (
                                                <Send className="size-4" />
                                            )}
                                            {t(
                                                'core.invitations.manage.submit',
                                            )}
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>
                            {t('core.invitations.manage.pending_title')}
                        </CardTitle>
                        <CardDescription>
                            {t('core.invitations.manage.description')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {invitations.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {t('core.invitations.manage.pending_empty')}
                            </p>
                        ) : (
                            <ul className="flex flex-col divide-y">
                                {invitations.map((invitation) => (
                                    <li
                                        key={invitation.hash_id}
                                        className="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                                    >
                                        <div className="grid gap-1">
                                            <span className="text-sm font-medium">
                                                {invitation.email}
                                            </span>
                                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                <Badge variant="outline">
                                                    {invitation.role}
                                                </Badge>
                                                <span>
                                                    {t(
                                                        'core.invitations.manage.th_expires',
                                                    )}
                                                    {': '}
                                                    {new Date(
                                                        invitation.expires_at,
                                                    ).toLocaleDateString()}
                                                </span>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <Form {...resend.form(invitation)}>
                                                {({ processing }) => (
                                                    <Button
                                                        type="submit"
                                                        variant="outline"
                                                        size="sm"
                                                        disabled={processing}
                                                    >
                                                        {processing && (
                                                            <Spinner />
                                                        )}
                                                        {t(
                                                            'core.invitations.manage.resend',
                                                        )}
                                                    </Button>
                                                )}
                                            </Form>

                                            <CancelInvitation
                                                invitation={invitation}
                                            />
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function CancelInvitation({ invitation }: { invitation: PendingInvitation }) {
    const { t } = useTranslations();

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="text-destructive hover:text-destructive"
                >
                    <Trash2 className="size-4" />
                    {t('core.invitations.manage.cancel')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t('core.invitations.manage.cancel_title')}
                    </DialogTitle>
                    <DialogDescription>
                        {t('core.invitations.manage.cancel_description', {
                            email: invitation.email,
                        })}
                    </DialogDescription>
                </DialogHeader>
                <Form {...destroy.form(invitation)}>
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="secondary" type="button">
                                    {t('core.common.cancel')}
                                </Button>
                            </DialogClose>
                            <Button
                                variant="destructive"
                                type="submit"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                {t('core.invitations.manage.cancel')}
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
