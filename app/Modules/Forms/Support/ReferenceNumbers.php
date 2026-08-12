<?php

namespace App\Modules\Forms\Support;

use App\Modules\Forms\Models\Form;
use Illuminate\Support\Facades\DB;

/**
 * Issues the human-friendly identity every submission carries: the form's
 * prefix, the year, and a running number — FIN-2026-0107.
 *
 * The sequence counts per process per year, and each new year starts back at
 * one. A number is never reused, including across a request that failed after
 * allocation: a gap in the sequence is harmless, a duplicate is two requests
 * answering to one name.
 */
final class ReferenceNumbers
{
    /**
     * Allocate the next reference for the form.
     *
     * The sequence row is locked for the duration, so two submissions racing
     * each other are issued consecutive numbers rather than the same one.
     * Callers already inside a transaction get the lock's protection for the
     * whole of theirs.
     */
    public static function issue(Form $form): string
    {
        $year = (int) now()->format('Y');

        return DB::transaction(function () use ($form, $year): string {
            $sequence = $form->getConnection()
                ->table('form_sequences')
                ->where('form_id', $form->getKey())
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                DB::table('form_sequences')->insert([
                    'organization_id' => $form->organization_id,
                    'form_id' => $form->getKey(),
                    'year' => $year,
                    'last_number' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $next = 1;
            } else {
                $next = ((int) $sequence->last_number) + 1;

                DB::table('form_sequences')
                    ->where('id', $sequence->id)
                    ->update(['last_number' => $next, 'updated_at' => now()]);
            }

            return sprintf('%s-%d-%04d', $form->prefix, $year, $next);
        });
    }
}
