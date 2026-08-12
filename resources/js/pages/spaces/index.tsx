import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { KanbanSquare } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslations } from '@/hooks/use-translations';
import { show as showBoard } from '@/routes/boards';
import { index } from '@/routes/spaces';

type Props = {
    spaces: {
        hash_id: string;
        name: string;
        is_default: boolean;
        boards: { hash_id: string; name: string }[];
    }[];
};

export default function SpacesIndex({ spaces }: Props) {
    const { t } = useTranslations();

    setLayoutProps({
        breadcrumbs: [{ title: t('boards.spaces.title'), href: index() }],
    });

    return (
        <>
            <Head title={t('boards.spaces.title')} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold">
                        {t('boards.spaces.title')}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {t('boards.spaces.description')}
                    </p>
                </div>

                {spaces.map((space) => (
                    <section
                        key={space.hash_id}
                        className="flex flex-col gap-3"
                    >
                        <div className="flex items-center gap-2">
                            <h2 className="font-medium">{space.name}</h2>
                            {space.is_default && (
                                <Badge variant="secondary">
                                    {t('boards.spaces.default')}
                                </Badge>
                            )}
                        </div>

                        {space.boards.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {t('boards.spaces.no_boards')}
                            </p>
                        ) : (
                            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {space.boards.map((board) => (
                                    <Link
                                        key={board.hash_id}
                                        href={showBoard(board.hash_id)}
                                        data-test={`board-${board.hash_id}`}
                                    >
                                        <Card className="transition-colors hover:bg-accent/50">
                                            <CardContent className="flex items-center gap-3 py-4">
                                                <KanbanSquare className="size-5 text-muted-foreground" />
                                                <span className="font-medium">
                                                    {board.name}
                                                </span>
                                            </CardContent>
                                        </Card>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </section>
                ))}
            </div>
        </>
    );
}
