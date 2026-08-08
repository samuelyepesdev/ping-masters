import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, Gamepad2, LayoutGrid, LayoutTemplate, ListChecks, Shield, Swords, Trophy, User as UserIcon } from 'lucide-react';
import AppLogo from './app-logo';

const footerNavItems: NavItem[] = [
    {
        title: 'Reglas ITTF',
        url: 'https://www.ittf.com/handbook/',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const roles = auth.user?.roles ?? [];
    const isSuperAdmin = roles.includes('super_admin');
    const isOrganizer = roles.includes('organizer') || isSuperAdmin;
    const isReferee = roles.includes('referee') || isSuperAdmin;
    const isPlayer = roles.includes('player') || isSuperAdmin;

    const mainNavItems: NavItem[] = [
        {
            title: 'Panel',
            url: '/dashboard',
            icon: LayoutGrid,
        },
        ...(isOrganizer
            ? [
                  {
                      title: 'Torneos',
                      url: '/tournaments',
                      icon: Trophy,
                  },
                  {
                      title: 'Plantillas de Categorías',
                      url: '/plantillas/categorias',
                      icon: LayoutTemplate,
                  },
                  {
                      title: 'Plantillas de Formularios',
                      url: '/plantillas/formularios',
                      icon: ListChecks,
                  },
              ]
            : []),
        ...(isReferee
            ? [
                  {
                      title: 'Arbitraje',
                      url: '/scoring',
                      icon: Swords,
                  },
              ]
            : []),
        ...(isPlayer
            ? [
                  {
                      title: 'Retos',
                      url: '/retos',
                      icon: Gamepad2,
                  },
                  {
                      title: 'Mi Perfil',
                      url: '/profile/me',
                      icon: UserIcon,
                  },
              ]
            : []),
        {
            title: 'Ranking',
            url: '/ranking',
            icon: Trophy,
        },
        ...(isSuperAdmin
            ? [
                  {
                      title: 'Usuarios y Roles',
                      url: '/admin/users',
                      icon: Shield,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
