import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PaginatedData, type User } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const ROLE_LABELS: Record<string, string> = {
    super_admin: 'Super admin',
    organizer: 'Organizador',
    referee: 'Árbitro',
    player: 'Jugador',
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Usuarios y roles', href: '/admin/users' }];

interface Props {
    users: PaginatedData<User>;
    filters: { search?: string };
    availableRoles: string[];
}

export default function AdminUsersIndex({ users, filters, availableRoles }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [editing, setEditing] = useState<User | null>(null);
    const [selectedRoles, setSelectedRoles] = useState<string[]>([]);

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('admin.users.index'), { search }, { preserveState: true, replace: true });
    };

    function openEdit(user: User) {
        setEditing(user);
        setSelectedRoles(user.roles);
    }

    function toggleRole(role: string, checked: boolean) {
        setSelectedRoles((prev) => (checked ? [...prev, role] : prev.filter((r) => r !== role)));
    }

    function saveRoles() {
        if (!editing) return;

        router.patch(
            route('admin.users.roles.update', editing.id),
            { roles: selectedRoles },
            { preserveScroll: true, onSuccess: () => setEditing(null) },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Usuarios y roles" />
            <div className="space-y-6 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Usuarios y roles</h1>
                    <p className="text-muted-foreground">Administra quién puede organizar, arbitrar o jugar en la plataforma.</p>
                </div>

                <form onSubmit={submitSearch} className="max-w-sm">
                    <Input placeholder="Buscar por nombre o correo..." value={search} onChange={(e) => setSearch(e.target.value)} />
                </form>

                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Usuario</TableHead>
                                <TableHead>Club</TableHead>
                                <TableHead>Roles</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.data.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell>
                                        <div className="flex items-center gap-2">
                                            <UserAvatar name={user.name} />
                                            <div>
                                                <p className="font-medium">{user.name}</p>
                                                <p className="text-xs text-muted-foreground">{user.email}</p>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">{user.club?.name ?? '—'}</TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap gap-1">
                                            {user.roles.length === 0 && <span className="text-xs text-muted-foreground">Sin rol</span>}
                                            {user.roles.map((role) => (
                                                <Badge key={role} variant="secondary">
                                                    {ROLE_LABELS[role] ?? role}
                                                </Badge>
                                            ))}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button variant="ghost" size="sm" onClick={() => openEdit(user)}>
                                            <Pencil className="size-4" />
                                            Editar roles
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {users.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={4} className="text-center text-sm text-muted-foreground">
                                        No se encontraron usuarios.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <Dialog open={!!editing} onOpenChange={(open) => !open && setEditing(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Roles de {editing?.name}</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3">
                        {availableRoles.map((role) => (
                            <label key={role} className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={selectedRoles.includes(role)}
                                    onCheckedChange={(checked) => toggleRole(role, checked === true)}
                                />
                                {ROLE_LABELS[role] ?? role}
                            </label>
                        ))}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setEditing(null)}>
                            Cancelar
                        </Button>
                        <Button onClick={saveRoles}>Guardar</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function UserAvatar({ name }: { name: string }) {
    const getInitials = useInitials();

    return (
        <Avatar className="size-8">
            <AvatarFallback className="text-xs">{getInitials(name)}</AvatarFallback>
        </Avatar>
    );
}
