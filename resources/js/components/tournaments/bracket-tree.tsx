import { MatchCard } from '@/components/tournaments/match-card';
import { type BracketMatch, type RoundStage } from '@/types';

const STAGE_LABELS: Record<RoundStage, string> = {
    main_bracket: 'Llave principal',
    winners_bracket: 'Llave de ganadores',
    losers_bracket: 'Llave de perdedores',
    grand_final: 'Gran final',
    group_stage: 'Fase de grupos',
    swiss: 'Suizo',
};

const STAGE_ORDER: RoundStage[] = ['winners_bracket', 'losers_bracket', 'main_bracket', 'grand_final'];

export function BracketTree({
    matches,
    tournamentId,
    divisionId,
    canRecordResult,
}: {
    matches: BracketMatch[];
    tournamentId: number;
    divisionId: number;
    canRecordResult: boolean;
}) {
    const stages = STAGE_ORDER.filter((stage) => matches.some((m) => m.stage === stage));

    return (
        <div className="space-y-8">
            {stages.map((stage) => {
                const stageMatches = matches.filter((m) => m.stage === stage);
                const roundNumbers = [...new Set(stageMatches.map((m) => m.round_number ?? 0))].sort((a, b) => a - b);

                return (
                    <div key={stage}>
                        <h3 className="mb-3 text-sm font-semibold text-muted-foreground uppercase tracking-wide">{STAGE_LABELS[stage]}</h3>
                        <div className="flex gap-6 overflow-x-auto pb-4">
                            {roundNumbers.map((roundNumber) => {
                                const roundMatches = stageMatches.filter((m) => m.round_number === roundNumber);

                                return (
                                    <div key={roundNumber} className="flex shrink-0 flex-col justify-around gap-6">
                                        <p className="text-center text-xs font-medium text-muted-foreground">{roundMatches[0]?.round_name}</p>
                                        {roundMatches.map((match) => (
                                            <MatchCard
                                                key={match.id}
                                                match={match}
                                                canRecordResult={canRecordResult}
                                                resultRoute={route('tournaments.divisions.matches.result', [tournamentId, divisionId, match.id])}
                                            />
                                        ))}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
