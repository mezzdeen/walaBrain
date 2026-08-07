<?php

namespace App\Modules\Boards\Enums;

/**
 * The ten types a board's fields are built from.
 *
 * Ten, and no eleventh: a board is described entirely by these, and a form
 * collects into the same set, so a field behaves identically wherever it
 * appears. There is no custom or open-ended type, which is what keeps a node
 * from one board readable by a report written against another.
 */
enum FieldType: string
{
    /** A short piece of identifying information, e.g. a beneficiary name. */
    case Text = 'text';

    /** A free-form description too long for a single line. */
    case LongText = 'long_text';

    /** A plain count or quantity, with no currency attached. */
    case Number = 'number';

    /** A monetary amount, e.g. a budget or a payment. */
    case Money = 'money';

    /** A single calendar date other work can be paced against. */
    case Date = 'date';

    /** One choice from a fixed list, e.g. a cost centre. */
    case SingleSelect = 'single_select';

    /** Any number of choices from a fixed list, e.g. channels. */
    case MultiSelect = 'multi_select';

    /** A person on the platform, e.g. a campaign owner. */
    case Person = 'person';

    /** The node's stage in its process. */
    case Status = 'status';

    /** A single uploaded attachment, e.g. an invoice. */
    case File = 'file';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Whether the type needs a list of options defined alongside it.
     */
    public function hasOptions(): bool
    {
        return in_array($this, [self::SingleSelect, self::MultiSelect, self::Status], true);
    }

    /**
     * Whether a value of this type may hold more than one entry.
     */
    public function isMultiple(): bool
    {
        return $this === self::MultiSelect;
    }

    /**
     * How a value of this type is written into a node's `values` column.
     *
     * Fixed per type and honoured everywhere, because sorting and filtering read
     * these back out of JSON and a single value stored the wrong way breaks the
     * column for every node on the board, not just its own. Money and Number are
     * numbers rather than formatted strings — a thousands separator makes a
     * numeric sort throw — and Date is ISO 8601, which sorts chronologically as
     * text without needing a cast at all.
     */
    public function storedAs(): string
    {
        return match ($this) {
            self::Number, self::Money => 'number, never a formatted string',
            self::Date => 'ISO 8601 date, so text ordering is chronological ordering',
            self::MultiSelect => 'array of option values',
            self::Person => 'user id',
            self::File => 'stored file path',
            default => 'string',
        };
    }
}
