import { EmailCombobox } from '@/components/email-combobox';
import { useTranslations } from '@/hooks/use-translations';
import { search } from '@/routes/super/users';

interface OwnerEmailComboboxProps {
    /** Field name the surrounding `<Form>` submits this address under. */
    name: string;
    error?: string;
}

/**
 * The owner field on the platform's create-organization form.
 *
 * A thin wrapping of {@see EmailCombobox} that points it at the platform-wide
 * account search and words it for ownership.
 */
export function OwnerEmailCombobox({ name, error }: OwnerEmailComboboxProps) {
    const { t } = useTranslations();

    return (
        <EmailCombobox
            name={name}
            error={error}
            inputId="owner_email"
            searchUrl={(q) => search.url({ query: { q } })}
            copy={{
                label: t('core.organizations.owner_email'),
                placeholder: t('core.organizations.owner_email_placeholder'),
                searching: t('core.organizations.owner_searching'),
                selectedNote: `${t('core.organizations.owner_will_be_added')} ${t('core.organizations.owner_independent')}`,
                noResults: t('core.organizations.owner_no_results'),
                willBeInvited: t('core.organizations.owner_will_be_invited'),
                clear: t('core.organizations.owner_clear'),
            }}
        />
    );
}
