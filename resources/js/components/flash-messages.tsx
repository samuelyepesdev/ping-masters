import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export function FlashMessages({ className }: { className?: string }) {
    const { flash } = usePage<SharedData>().props;

    if (!flash?.success && !flash?.error) return null;

    return (
        <div className={cn('space-y-2', className)}>
            {flash.success && (
                <div className="rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-700 dark:text-green-400">
                    {flash.success}
                </div>
            )}
            {flash.error && (
                <div className="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-700 dark:text-red-400">
                    {flash.error}
                </div>
            )}
        </div>
    );
}
