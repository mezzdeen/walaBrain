import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/use-translations';

export type PermissionGroups = Record<string, string[]>;

type Props = {
    groups: PermissionGroups;
    selected: string[];
    onChange: (selected: string[]) => void;
    disabled?: boolean;
};

/**
 * The permission checkboxes for a role, laid out one column per subject.
 *
 * Names arrive as `subject.ability` (occasionally `subject.thing.ability`), so
 * the group heading carries the subject and each checkbox only has to label the
 * ability.
 */
export function PermissionMatrix({
    groups,
    selected,
    onChange,
    disabled = false,
}: Props) {
    const { t } = useTranslations();

    const toggle = (permission: string, checked: boolean): void => {
        onChange(
            checked
                ? [...selected, permission]
                : selected.filter((name) => name !== permission),
        );
    };

    const label = (permission: string, group: string): string =>
        t(`core.roles.abilities.${permission.slice(group.length + 1)}`);

    return (
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {Object.entries(groups).map(([group, permissions]) => (
                <div key={group} className="flex flex-col gap-3">
                    <p className="text-sm font-medium">
                        {t(`core.roles.groups.${group}`)}
                    </p>

                    <div className="flex flex-col gap-2">
                        {permissions.map((permission) => (
                            <div
                                key={permission}
                                className="flex items-center gap-2"
                            >
                                <Checkbox
                                    id={permission}
                                    name="permissions[]"
                                    value={permission}
                                    disabled={disabled}
                                    checked={selected.includes(permission)}
                                    onCheckedChange={(checked) =>
                                        toggle(permission, checked === true)
                                    }
                                />
                                <Label
                                    htmlFor={permission}
                                    className="text-sm font-normal text-muted-foreground"
                                >
                                    {label(permission, group)}
                                </Label>
                            </div>
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
}
