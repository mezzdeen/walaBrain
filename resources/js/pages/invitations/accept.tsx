import { Form, Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/hooks/use-translations';
import { accept } from '@/routes/invitations';

type Props = {
    organization: { name: string };
    role: string;
    token: string;
};

export default function AcceptMemberInvitation({
    organization,
    role,
    token,
}: Props) {
    const { t } = useTranslations();

    return (
        <>
            <Head
                title={t('core.invitations.accept.title', {
                    name: organization.name,
                })}
            />

            <div className="flex h-full flex-1 items-center justify-center p-4">
                <Card className="w-full max-w-md">
                    <CardHeader>
                        <CardTitle>
                            {t('core.invitations.accept.title', {
                                name: organization.name,
                            })}
                        </CardTitle>
                        <CardDescription>
                            {t('core.invitations.accept.description', {
                                name: organization.name,
                                role,
                            })}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form {...accept.form(token)}>
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    {t('core.invitations.accept.submit')}
                                </Button>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
