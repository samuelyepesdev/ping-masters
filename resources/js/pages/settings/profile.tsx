import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Loader2, Trash2 } from 'lucide-react';
import { ChangeEvent, FormEventHandler, useRef, useState } from 'react';

import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración de perfil',
        href: '/settings/profile',
    },
];

export default function Profile({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
    const { auth } = usePage<SharedData>().props;
    const getInitials = useInitials();
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [avatarProcessing, setAvatarProcessing] = useState(false);

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        name: auth.user.name,
        email: auth.user.email,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    function handleAvatarSelected(e: ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (!file) return;

        setAvatarProcessing(true);
        router.post(
            route('profile.avatar.update'),
            { avatar: file },
            {
                forceFormData: true,
                preserveScroll: true,
                onFinish: () => {
                    setAvatarProcessing(false);
                    if (fileInputRef.current) fileInputRef.current.value = '';
                },
            },
        );
    }

    function removeAvatar() {
        setAvatarProcessing(true);
        router.delete(route('profile.avatar.destroy'), {
            preserveScroll: true,
            onFinish: () => setAvatarProcessing(false),
        });
    }

    const isVerified = auth.user.email_verified_at !== null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configuración de perfil" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Información del perfil" description="Actualiza tu nombre y correo electrónico" />

                    <div className="flex items-center gap-4">
                        <Avatar className="size-16">
                            <AvatarImage src={auth.user.avatar ?? undefined} alt={auth.user.name} />
                            <AvatarFallback className="text-lg">{getInitials(auth.user.name)}</AvatarFallback>
                        </Avatar>

                        <div className="space-y-1.5">
                            <div className="flex flex-wrap items-center gap-2">
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept="image/*"
                                    className="hidden"
                                    onChange={handleAvatarSelected}
                                    disabled={avatarProcessing || !isVerified}
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    disabled={avatarProcessing || !isVerified}
                                    onClick={() => fileInputRef.current?.click()}
                                >
                                    {avatarProcessing && <Loader2 className="size-4 animate-spin" />}
                                    Cambiar foto
                                </Button>
                                {auth.user.avatar && (
                                    <Button type="button" variant="ghost" size="sm" disabled={avatarProcessing} onClick={removeAvatar}>
                                        <Trash2 className="size-4" />
                                        Quitar
                                    </Button>
                                )}
                            </div>
                            {!isVerified && (
                                <p className="text-xs text-muted-foreground">Verifica tu correo electrónico para poder cambiar tu foto de perfil.</p>
                            )}
                        </div>
                    </div>

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nombre</Label>

                            <Input
                                id="name"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                autoComplete="name"
                                placeholder="Nombre completo"
                            />

                            <InputError className="mt-2" message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Correo electrónico</Label>

                            <Input
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                                autoComplete="username"
                                placeholder="Correo electrónico"
                            />

                            <InputError className="mt-2" message={errors.email} />

                            {isVerified ? (
                                <Badge variant="secondary" className="mt-1 w-fit gap-1.5 border-transparent bg-green-500/15 text-green-700 dark:text-green-400">
                                    <CheckCircle2 className="size-3.5" />
                                    Correo verificado
                                </Badge>
                            ) : (
                                mustVerifyEmail && (
                                    <div>
                                        <p className="mt-2 text-sm text-neutral-800 dark:text-neutral-200">
                                            Tu correo electrónico no está verificado.
                                            <Link
                                                href={route('verification.send')}
                                                method="post"
                                                as="button"
                                                className="ml-1 rounded-md text-sm text-neutral-600 underline hover:text-neutral-900 focus:ring-2 focus:ring-offset-2 focus:outline-hidden dark:text-neutral-400 dark:hover:text-neutral-100"
                                            >
                                                Haz clic aquí para reenviar el correo de verificación.
                                            </Link>
                                        </p>

                                        {status === 'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                Se envió un nuevo enlace de verificación a tu correo electrónico.
                                            </div>
                                        )}
                                    </div>
                                )
                            )}
                        </div>

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>Guardar</Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">Guardado</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
