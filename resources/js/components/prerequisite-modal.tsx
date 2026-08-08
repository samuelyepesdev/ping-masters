import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { withReturnHere } from '@/lib/redirect-to';
import { Link } from '@inertiajs/react';

export interface PrerequisiteItem {
    satisfied: boolean;
    label: string;
    message: string;
    createUrl: string;
    createLabel: string;
}

export function PrerequisiteModal({ items, backUrl }: { items: PrerequisiteItem[]; backUrl: string }) {
    if (items.length === 0) return null;

    return (
        <AlertDialog open>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Antes de continuar</AlertDialogTitle>
                    <AlertDialogDescription>Necesitas crear lo siguiente antes de poder continuar:</AlertDialogDescription>
                </AlertDialogHeader>

                <div className="space-y-3">
                    {items.map((item) => (
                        <div key={item.label} className="rounded-lg border p-3">
                            <p className="font-medium">{item.label}</p>
                            <p className="mb-2 text-sm text-muted-foreground">{item.message}</p>
                            <Button size="sm" asChild>
                                <Link href={withReturnHere(item.createUrl)}>{item.createLabel}</Link>
                            </Button>
                        </div>
                    ))}
                </div>

                <AlertDialogFooter>
                    <Button variant="outline" asChild>
                        <Link href={backUrl}>Volver</Link>
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
