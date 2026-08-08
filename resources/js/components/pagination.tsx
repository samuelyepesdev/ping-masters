import { cn } from '@/lib/utils';
import { type PaginatedData } from '@/types';
import { Link } from '@inertiajs/react';

export function Pagination({ data }: { data: PaginatedData<unknown> }) {
    if (data.last_page <= 1) return null;

    return (
        <div className="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p className="text-sm text-muted-foreground">
                {data.total} en total · página {data.current_page} de {data.last_page}
            </p>
            <nav className="flex flex-wrap items-center gap-1">
                {data.links.map((link, index) =>
                    link.url ? (
                        <Link
                            key={index}
                            href={link.url}
                            preserveScroll
                            className={cn(
                                'flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm transition-colors',
                                link.active ? 'bg-primary text-primary-foreground' : 'border hover:bg-accent',
                            )}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            key={index}
                            className="flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm text-muted-foreground opacity-50"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ),
                )}
            </nav>
        </div>
    );
}
