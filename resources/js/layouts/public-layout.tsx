import { Button } from '@/components/ui/button';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Trophy } from 'lucide-react';
import { PropsWithChildren } from 'react';

export default function PublicLayout({ children }: PropsWithChildren) {
    const { auth, flash } = usePage<SharedData>().props;

    return (
        <div className="min-h-screen bg-background">
            <header className="border-b">
                <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                    <Link href={route('public.tournaments.index')} className="flex items-center gap-2 font-semibold">
                        <Trophy className="size-5 text-primary" />
                        Ping Masters
                    </Link>
                    <nav className="flex items-center gap-2">
                        {auth.user ? (
                            <Button asChild variant="outline">
                                <Link href={route('dashboard')}>Mi panel</Link>
                            </Button>
                        ) : (
                            <>
                                <Button asChild variant="ghost">
                                    <Link href={route('login')}>Iniciar sesión</Link>
                                </Button>
                                <Button asChild>
                                    <Link href={route('register')}>Crear cuenta</Link>
                                </Button>
                            </>
                        )}
                    </nav>
                </div>
            </header>
            <main className="mx-auto max-w-6xl px-4 py-8">
                {flash?.success && (
                    <div className="mb-6 rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-700 dark:text-green-400">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="mb-6 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-700 dark:text-red-400">
                        {flash.error}
                    </div>
                )}
                {children}
            </main>
        </div>
    );
}
