<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Admin;
use App\Modules\Core\Notifications\MailDeliveryCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MailDiagnosticsController extends Controller
{
    /**
     * Send a test message through the configured mailer.
     *
     * Answers "is mail broken, and if so where" after a settings change. The
     * message always goes to the requesting admin's own address: a recipient
     * taken from the request would make an authenticated relay out of a
     * diagnostic, which matters the moment this points at a real provider
     * instead of a sandbox.
     *
     * The response reports the settings that were used but cannot report
     * whether the send succeeded — the notification is queued, so the transport
     * is exercised by the worker long after this returns. Read it as "accepted,
     * with these settings"; the inbox and `failed_jobs` hold the verdict.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('super');

        $settings = $this->resolvedSettings();

        $admin->notify(new MailDeliveryCheck($settings, now()->toDateTimeString()));

        return response()->json([
            'queued' => true,
            'recipient' => $admin->email,
            'settings' => $settings,
            'queue_connection' => config('queue.default'),
        ]);
    }

    /**
     * The mail settings actually in force, read back from the resolved config
     * rather than the environment.
     *
     * Reading `.env` would miss a cached config, which is the exact case where
     * the settings on disk and the settings in use disagree — the failure this
     * endpoint exists to catch. The credentials are deliberately absent: this
     * is a diagnostic, not a secret dump.
     *
     * @return array{mailer: string, host: string|null, port: int|string|null, from: string|null}
     */
    private function resolvedSettings(): array
    {
        $mailer = (string) config('mail.default');

        return [
            'mailer' => $mailer,
            'host' => config("mail.mailers.{$mailer}.host"),
            'port' => config("mail.mailers.{$mailer}.port"),
            'from' => config('mail.from.address'),
        ];
    }
}
