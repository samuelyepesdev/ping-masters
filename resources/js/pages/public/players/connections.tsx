import { Pagination } from '@/components/pagination';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/hooks/use-initials';
import SmartLayout from '@/layouts/smart-layout';
import { type BreadcrumbItem, type PaginatedData, type Player } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { UserPlus, UserRoundCheck, Users } from 'lucide-react';
import { useState } from 'react';

interface Props {
    owner: Player;
    type: 'followers' | 'following';
    players: PaginatedData<Player>;
    viewerPlayerId?: number;
}

export default function PlayerConnections({ owner, type, players, viewerPlayerId }: Props) {
    const ownerName = owner.user?.name ?? 'Jugador';
    const title = type === 'followers' ? `Seguidores de ${ownerName}` : `${ownerName} sigue a`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Ranking', href: '/ranking' },
        { title: ownerName, href: route('public.players.show', owner.id) },
        { title: type === 'followers' ? 'Seguidores' : 'Siguiendo', href: '#' },
    ];

    return (
        <SmartLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
                    <p className="text-muted-foreground">
                        {players.total} {type === 'followers' ? 'seguidores' : 'jugadores seguidos'}
                    </p>
                </div>

                {players.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed py-16 text-center">
                        <Users className="size-10 text-muted-foreground" />
                        <p className="text-muted-foreground">
                            {type === 'followers' ? 'Todavía no tiene seguidores.' : 'Todavía no sigue a nadie.'}
                        </p>
                    </div>
                ) : (
                    <div className="divide-y rounded-xl border">
                        {players.data.map((player) => (
                            <ConnectionRow key={player.id} player={player} viewerId={viewerPlayerId} />
                        ))}
                    </div>
                )}

                <Pagination data={players} />
            </div>
        </SmartLayout>
    );
}

function ConnectionRow({ player, viewerId }: { player: Player; viewerId?: number }) {
    const getInitials = useInitials();
    const [following, setFollowing] = useState(player.is_following ?? false);
    const [toggling, setToggling] = useState(false);
    const isSelf = viewerId === player.id;

    function toggleFollow() {
        setToggling(true);
        const wasFollowing = following;
        const options = {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => setFollowing(!wasFollowing),
            onFinish: () => setToggling(false),
        };

        if (wasFollowing) {
            router.delete(route('public.players.unfollow', player.id), options);
        } else {
            router.post(route('public.players.follow', player.id), {}, options);
        }
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 p-4">
            <Link href={route('public.players.show', player.id)} className="flex items-center gap-3 hover:underline">
                <Avatar className="size-10">
                    <AvatarImage src={player.user?.avatar ?? undefined} alt={player.user?.name ?? ''} />
                    <AvatarFallback>{getInitials(player.user?.name ?? '?')}</AvatarFallback>
                </Avatar>
                <div>
                    <p className="font-medium">{player.user?.name}</p>
                    <p className="text-xs text-muted-foreground">{player.club?.name ?? 'Sin club'}</p>
                </div>
            </Link>

            <div className="flex items-center gap-2">
                <Badge variant="secondary">Nv. {player.level}</Badge>
                {!isSelf && viewerId !== undefined && (
                    <Button variant={following ? 'outline' : 'default'} size="sm" disabled={toggling} onClick={toggleFollow}>
                        {following ? <UserRoundCheck className="size-4" /> : <UserPlus className="size-4" />}
                        {following ? 'Siguiendo' : 'Seguir'}
                    </Button>
                )}
            </div>
        </div>
    );
}
