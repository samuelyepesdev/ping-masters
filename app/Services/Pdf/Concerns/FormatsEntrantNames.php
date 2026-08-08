<?php

namespace App\Services\Pdf\Concerns;

use App\Models\TournamentRegistrationDivision;

trait FormatsEntrantNames
{
    private function entrantLabel(?TournamentRegistrationDivision $entrant, bool $isBye = false): string
    {
        if (! $entrant) {
            return $isBye ? 'BYE' : 'Por definir';
        }

        $name = $entrant->registration->player->user->name;

        return $entrant->partner_name ? "{$name} / {$entrant->partner_name}" : $name;
    }
}
