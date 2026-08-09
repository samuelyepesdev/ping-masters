import { FlashMessages } from '@/components/flash-messages';
import { Button } from '@/components/ui/button';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function PublicLayout({ children }: PropsWithChildren) {
    const { auth } = usePage<SharedData>().props;

    return (
        <div className="min-h-screen bg-background">
            <header className="border-b">
                <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                    <Link href={route('home')} className="flex items-center gap-2 font-semibold">
                        <img src="/logo.png" alt="Ping Masters" className="size-8 rounded-full object-contain" />
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
                <FlashMessages className="mb-6" />
                {children}
            </main>
        </div>
    );
}
