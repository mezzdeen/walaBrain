import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index, update } from '@/routes/super/organizations';

type Props = {
    organization: {
        id: number;
        name: string;
    };
};

export default function EditOrganization({ organization }: Props) {
    return (
        <>
            <Head title={`Edit ${organization.name}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Edit organization</h1>
                    <p className="text-sm text-muted-foreground">
                        Update the organization details.
                    </p>
                </div>

                <Card className="max-w-xl">
                    <CardContent>
                        <Form
                            {...update.form(organization.id)}
                            className="flex flex-col gap-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            required
                                            autoFocus
                                            defaultValue={organization.name}
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="flex items-center gap-3">
                                        <Button type="submit" disabled={processing}>
                                            {processing && <Spinner />}
                                            Save changes
                                        </Button>
                                        <Button variant="ghost" asChild>
                                            <Link href={index()}>Cancel</Link>
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

EditOrganization.layout = {
    breadcrumbs: [
        { title: 'Organizations', href: index() },
        { title: 'Edit', href: index() },
    ],
};
