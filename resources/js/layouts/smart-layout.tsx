import AppLayout from '@/layouts/app-layout';
import PublicLayout from '@/layouts/public-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

/**
 * These pages (tournaments, ranking, player profiles) are public, but an authenticated user
 * browsing them should still see their app sidebar instead of the marketing chrome — only a
 * signed-out visitor gets the full public layout with its own header/nav.
 */
export default function SmartLayout({ children, breadcrumbs }: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    const { auth } = usePage<SharedData>().props;

    if (auth.user) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="mx-auto max-w-6xl px-4 py-8">{children}</div>
            </AppLayout>
        );
    }

    return <PublicLayout>{children}</PublicLayout>;
}
