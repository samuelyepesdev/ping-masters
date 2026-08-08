import { MatchCard } from '@/components/tournaments/match-card';
import { type BracketMatch, type RoundStage } from '@/types';
import { motion } from 'framer-motion';

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
    canScore,
}: {
    matches: BracketMatch[];
    tournamentId: number;
    divisionId: number;
    canScore: boolean;
}) {
    const stages = STAGE_ORDER.filter((stage) => matches.some((m) => m.stage === stage));
    let cardIndex = 0;

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
                                        {roundMatches.map((match) => {
                                            const delay = Math.min(cardIndex++, 24) * 0.035;

                                            return (
                                                <motion.div
                                                    key={match.id}
                                                    initial={{ opacity: 0, y: 12, scale: 0.96 }}
                                                    animate={{ opacity: 1, y: 0, scale: 1 }}
                                                    transition={{ duration: 0.35, delay, ease: 'easeOut' }}
                                                >
                                                    <MatchCard
                                                        match={match}
                                                        canScore={canScore}
                                                        scoreRoute={route('tournaments.divisions.matches.score', [tournamentId, divisionId, match.id])}
                                                        scorecardRoute={route('tournaments.divisions.matches.pdf.scorecard', [
                                                            tournamentId,
                                                            divisionId,
                                                            match.id,
                                                        ])}
                                                    />
                                                </motion.div>
                                            );
                                        })}
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
