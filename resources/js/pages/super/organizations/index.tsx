import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
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
import { Spinner } from '@/components/ui/spinner';
import { create, destroy, edit, index, show } from '@/routes/super/organizations';

type OrganizationRow = {
    id: number;
    name: string;
    users_count: number;
};

type Props = {
    organizations: OrganizationRow[];
};

export default function OrganizationsIndex({ organizations }: Props) {
    return (
        <>
            <Head title="Organizations" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Organizations</h1>
                        <p className="text-sm text-muted-foreground">
                            Manage the companies on the platform.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create()}>
                            <Plus className="size-4" />
                            New organization
                        </Link>
                    </Button>
                </div>

                <Card className="overflow-hidden p-0">
                    {organizations.length === 0 ? (
                        <div className="flex flex-col items-center gap-2 p-12 text-center">
                            <p className="text-sm font-medium">
                                No organizations yet
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Create your first organization to get started.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="px-4 py-3 font-medium">
                                            Name
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Users
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {organizations.map((organization) => (
                                        <tr
                                            key={organization.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={show(organization.id)}
                                                    className="font-medium hover:underline"
                                                >
                                                    {organization.name}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant="secondary">
                                                    {organization.users_count}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={edit(
                                                                organization.id,
                                                            )}
                                                        >
                                                            <Pencil className="size-4" />
                                                            Edit
                                                        </Link>
                                                    </Button>
                                                    <DeleteOrganization
                                                        organization={
                                                            organization
                                                        }
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>
            </div>
        </>
    );
}

function DeleteOrganization({
    organization,
}: {
    organization: OrganizationRow;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="text-destructive hover:text-destructive"
                >
                    <Trash2 className="size-4" />
                    Delete
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete organization</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete{' '}
                        <span className="font-medium">{organization.name}</span>?
                        This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <Form {...destroy.form(organization.id)}>
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="secondary" type="button">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button
                                variant="destructive"
                                type="submit"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Delete
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

OrganizationsIndex.layout = {
    breadcrumbs: [{ title: 'Organizations', href: index() }],
};
