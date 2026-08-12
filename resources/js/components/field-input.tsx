import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslations } from '@/hooks/use-translations';

export type FieldDefinition = {
    hash_id: string;
    name: string;
    type: string;
    options: string[] | null;
    help?: string | null;
    is_required: boolean;
};

/**
 * One board field rendered as the input its type calls for. Labels and options
 * are authored content and arrive in the working language; only the chrome
 * around them is translated.
 */
export function FieldInput({
    field,
    defaultValue,
    error,
}: {
    field: FieldDefinition;
    defaultValue?: string;
    error?: string;
}) {
    const { t } = useTranslations();
    const name = `values[${field.hash_id}]`;
    const id = `field-${field.hash_id}`;

    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>
                {field.name}
                {!field.is_required && (
                    <span className="ms-1 text-xs text-muted-foreground">
                        ({t('forms.forms.optional')})
                    </span>
                )}
            </Label>

            {(field.type === 'single_select' || field.type === 'status') && (
                <Select name={name} defaultValue={defaultValue}>
                    <SelectTrigger id={id} data-test={`field-${field.hash_id}`}>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {(field.options ?? []).map((option) => (
                            <SelectItem key={option} value={option}>
                                {option}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}

            {field.type === 'long_text' && (
                <textarea
                    id={id}
                    name={name}
                    defaultValue={defaultValue}
                    data-test={`field-${field.hash_id}`}
                    className="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                />
            )}

            {(field.type === 'text' ||
                field.type === 'number' ||
                field.type === 'money' ||
                field.type === 'date') && (
                <Input
                    id={id}
                    name={name}
                    defaultValue={defaultValue}
                    data-test={`field-${field.hash_id}`}
                    type={
                        field.type === 'date'
                            ? 'date'
                            : field.type === 'text'
                              ? 'text'
                              : 'number'
                    }
                    step={field.type === 'money' ? '0.01' : undefined}
                    autoComplete="off"
                />
            )}

            {field.help && (
                <p className="text-xs text-muted-foreground">{field.help}</p>
            )}
            <InputError message={error} />
        </div>
    );
}
