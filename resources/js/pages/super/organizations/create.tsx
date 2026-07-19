import { Form, Head, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { create, index, store } from '@/routes/super/organizations';

export default function CreateOrganization() {
    return (
        <>
            <Head title="New organization" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">New organization</h1>
                    <p className="text-sm text-muted-foreground">
                        Add a new company to the platform.
                    </p>
                </div>

                <Card className="max-w-xl">
                    <CardContent>
                        <Form
                            {...store.form()}
                            resetOnSuccess
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
                                            placeholder="Acme Inc"
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="flex items-center gap-3">
                                        <Button type="submit" disabled={processing}>
                                            {processing && <Spinner />}
                                            Create organization
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

CreateOrganization.layout = {
    breadcrumbs: [
        { title: 'Organizations', href: index() },
        { title: 'New', href: create() },
    ],
};
