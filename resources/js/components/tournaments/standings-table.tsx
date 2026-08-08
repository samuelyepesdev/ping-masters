import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type StandingRow } from '@/types';

export function StandingsTable({ standings }: { standings: StandingRow[] }) {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead className="w-10">#</TableHead>
                    <TableHead>Jugador</TableHead>
                    <TableHead className="text-right">PJ</TableHead>
                    <TableHead className="text-right">G</TableHead>
                    <TableHead className="text-right">P</TableHead>
                    <TableHead className="text-right">Pts</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {standings.map((row, index) => (
                    <TableRow key={row.entrant_id}>
                        <TableCell className="text-muted-foreground">{index + 1}</TableCell>
                        <TableCell className="font-medium">{row.name}</TableCell>
                        <TableCell className="text-right">{row.played}</TableCell>
                        <TableCell className="text-right">{row.wins}</TableCell>
                        <TableCell className="text-right">{row.losses}</TableCell>
                        <TableCell className="text-right font-semibold">{row.points}</TableCell>
                    </TableRow>
                ))}
                {standings.length === 0 && (
                    <TableRow>
                        <TableCell colSpan={6} className="text-center text-sm text-muted-foreground">
                            Aún no hay resultados.
                        </TableCell>
                    </TableRow>
                )}
            </TableBody>
        </Table>
    );
}
