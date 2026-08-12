import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useCan } from '@/hooks/use-can';
import { useTranslations } from '@/hooks/use-translations';
import { index as invitationsIndex } from '@/routes/invitations';
import { index, manager as updateManager } from '@/routes/members';

type RoleOption = {
    value: string;
    label: string;
};

type Member = {
    hash_id: string;
    full_name: string;
    email: string;
    roles: string[];
    manager: string | null;
};

type ManagerOption = {
    hash_id: string;
    name: string;
};

type Props = {
    members: Member[];
    managerOptions: ManagerOption[];
    roles: RoleOption[];
    filters: {
        search: string;
        role: string;
    };
};

// Radix Select has no empty-string item, so "all roles" carries its own value
// and is translated back to an unset filter when navigating.
const ALL_ROLES = 'all';

// Same trick for the manager column: "nobody" is a real choice.
const NO_MANAGER = 'none';

export default function Members({
    members,
    managerOptions,
    roles,
    filters,
}: Props) {
    const { t } = useTranslations();
    const { can } = useCan();

    const [search, setSearch] = useState(filters.search);

    setLayoutProps({
        breadcrumbs: [{ title: t('core.members.title'), href: index() }],
    });

    // One place the current filters turn into a visit, so the search box and the
    // role dropdown stay in step and neither drops the other's value.
    const apply = (next: { search?: string; role?: string }): void => {
        const query = {
            search: next.search ?? search,
            role: next.role ?? filters.role,
        };

        router.get(
            index.url({
                query: {
                    search: query.search || undefined,
                    role: query.role || undefined,
                },
            }),
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <>
            <Head title={t('core.members.title')} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">
                            {t('core.members.title')}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {t('core.members.description')}
                        </p>
                    </div>
                    {can('members.invite') && (
                        <Button asChild>
                            <Link href={invitationsIndex()}>
                                <Plus className="size-4" />
                                {t('core.members.add')}
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            apply({ search });
                        }}
                        className="flex items-center gap-2"
                    >
                        <Input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t('core.members.search_placeholder')}
                            className="w-64"
                        />
                        <Button type="submit" variant="outline">
                            <Search className="size-4" />
                            {t('core.members.search')}
                        </Button>
                    </form>

                    <Select
                        value={filters.role || ALL_ROLES}
                        onValueChange={(value) =>
                            apply({ role: value === ALL_ROLES ? '' : value })
                        }
                    >
                        <SelectTrigger className="w-52">
                            <SelectValue
                                placeholder={t(
                                    'core.members.role_filter_placeholder',
                                )}
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL_ROLES}>
                                {t('core.members.all_roles')}
                            </SelectItem>
                            {roles.map((role) => (
                                <SelectItem key={role.value} value={role.value}>
                                    {role.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <Card className="overflow-hidden p-0">
                    {members.length === 0 ? (
                        <div className="flex flex-col items-center gap-2 p-12 text-center">
                            <p className="text-sm font-medium">
                                {t('core.members.empty_title')}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {t('core.members.empty_description')}
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-start text-muted-foreground">
                                        <th className="px-4 py-3 font-medium">
                                            {t('core.members.th_name')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('core.members.th_role')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('core.members.manager')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {members.map((member) => (
                                        <tr
                                            key={member.hash_id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-4 py-3">
                                                <div className="font-medium">
                                                    {member.full_name}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {member.email}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                {member.roles.length === 0 ? (
                                                    <span className="text-muted-foreground">
                                                        {t(
                                                            'core.members.no_role',
                                                        )}
                                                    </span>
                                                ) : (
                                                    <div className="flex flex-wrap gap-1">
                                                        {member.roles.map(
                                                            (role) => (
                                                                <Badge
                                                                    key={role}
                                                                    variant="secondary"
                                                                >
                                                                    {role}
                                                                </Badge>
                                                            ),
                                                        )}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Select
                                                    value={
                                                        member.manager ??
                                                        NO_MANAGER
                                                    }
                                                    onValueChange={(value) =>
                                                        router.patch(
                                                            updateManager(
                                                                member.hash_id,
                                                            ).url,
                                                            {
                                                                manager:
                                                                    value ===
                                                                    NO_MANAGER
                                                                        ? null
                                                                        : value,
                                                            },
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger
                                                        className="w-44"
                                                        data-test={`manager-${member.hash_id}`}
                                                    >
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            value={NO_MANAGER}
                                                        >
                                                            {t(
                                                                'core.members.no_manager',
                                                            )}
                                                        </SelectItem>
                                                        {managerOptions
                                                            .filter(
                                                                (option) =>
                                                                    option.hash_id !==
                                                                    member.hash_id,
                                                            )
                                                            .map((option) => (
                                                                <SelectItem
                                                                    key={
                                                                        option.hash_id
                                                                    }
                                                                    value={
                                                                        option.hash_id
                                                                    }
                                                                >
                                                                    {
                                                                        option.name
                                                                    }
                                                                </SelectItem>
                                                            ))}
                                                    </SelectContent>
                                                </Select>
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
