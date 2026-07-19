import { Form, Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { logout } from '@/routes/super';

export default function SuperDashboard() {
    return (
        <>
            <Head title="Admin dashboard" />

            <div className="flex min-h-svh flex-col bg-background">
                <header className="flex items-center justify-between border-b border-sidebar-border/70 px-6 py-4 dark:border-sidebar-border">
                    <div>
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Admin platform
                        </p>
                        <h1 className="text-lg font-semibold">Dashboard</h1>
                    </div>

                    <Form {...logout.form()}>
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="outline"
                                size="sm"
                                disabled={processing}
                                data-test="super-logout-button"
                            >
                                Log out
                            </Button>
                        )}
                    </Form>
                </header>

                <main className="flex flex-1 flex-col gap-4 p-6">
                    <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                        {[0, 1, 2].map((i) => (
                            <div
                                key={i}
                                className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                            >
                                <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                            </div>
                        ))}
                    </div>
                    <div className="relative min-h-[60vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                </main>
            </div>
        </>
    );
}
