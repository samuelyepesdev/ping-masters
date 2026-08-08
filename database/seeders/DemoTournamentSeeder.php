<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentDivision;
use App\Models\TournamentRegistration;
use App\Models\TournamentRegistrationDivision;
use App\Models\User;
use App\Services\Brackets\BracketGeneratorFactory;
use App\Services\Scoring\MatchScoringService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds one fully browsable demo tournament with a division per bracket format, so the
 * app can be explored manually in the browser. Not called from DatabaseSeeder's default
 * run() — run it explicitly with `php artisan db:seed --class=DemoTournamentSeeder`.
 */
class DemoTournamentSeeder extends Seeder
{
    private const DEMO_NAMES = [
        'Sofía Ramírez', 'Mateo González', 'Valentina Torres', 'Santiago Herrera',
        'Isabella Rojas', 'Nicolás Morales', 'Camila Vargas', 'Sebastián Castro',
        'Lucía Mendoza', 'Emiliano Silva', 'Martina Ortiz', 'Diego Paredes',
        'Renata Salazar', 'Joaquín Fuentes', 'Antonella Reyes', 'Benjamín Aguilar',
        'Julieta Campos', 'Maximiliano Cruz', 'Regina Navarro', 'Tomás Delgado',
        'Emilia Guerrero', 'Agustín Molina', 'Victoria Peña', 'Gabriel Cabrera',
        'Constanza Ibarra', 'Ignacio Soto', 'Amparo Vega', 'Rodrigo Bravo',
        'Josefina Luna', 'Álvaro Contreras', 'Paulina Rivas', 'Cristóbal Espinoza',
        'Fernanda Acosta', 'Lorenzo Duarte', 'Antonia Miranda', 'Andrés Cordero',
        'Milagros Suárez', 'Emiliano Paz', 'Dominga Ríos', 'Vicente Lara',
    ];

    public function run(): void
    {
        $organizer = User::firstOrCreate(
            ['email' => 'organizador@pingmasters.test'],
            ['name' => 'Organizador Demo', 'password' => Hash::make('password'), 'email_verified_at' => now()],
        );
        $organizer->assignRole('organizer');

        $club = Club::firstOrCreate(['name' => 'Club Demo Ping Masters'], ['city' => 'Bogotá', 'country' => 'Colombia']);

        $tournament = Tournament::updateOrCreate(
            ['slug' => 'torneo-demostracion-ping-masters'],
            [
                'name' => 'Torneo Demostración Ping Masters',
                'description' => 'Torneo de ejemplo con una categoría por cada modalidad soportada, para explorar la plataforma.',
                'venue' => 'Coliseo Ping Masters',
                'city' => 'Bogotá',
                'club_id' => $club->id,
                'created_by' => $organizer->id,
                'status' => 'registration_open',
                'start_date' => now()->subDay(),
                'end_date' => now()->addDays(2),
                'registration_opens_at' => now()->subWeek(),
                'registration_closes_at' => now()->addDay(),
                'max_participants' => 100,
            ],
        );

        $tournament->registrationFields()->delete();
        $tournament->registrationFields()->createMany([
            [
                'label' => 'Talla de camiseta',
                'field_type' => 'select',
                'options' => ['S', 'M', 'L', 'XL'],
                'is_required' => true,
                'display_order' => 0,
            ],
            [
                'label' => 'Alergias o condiciones médicas',
                'field_type' => 'textarea',
                'is_required' => false,
                'display_order' => 1,
            ],
        ]);

        $names = self::DEMO_NAMES;
        shuffle($names);
        $nameIndex = 0;
        $nextName = function () use (&$names, &$nameIndex) {
            $name = $names[$nameIndex % count($names)].($nameIndex >= count($names) ? ' '.(intdiv($nameIndex, count($names)) + 1) : '');
            $nameIndex++;

            return $name;
        };

        $makePlayer = function (int $rating) use ($nextName, $club) {
            $name = $nextName();
            $user = User::firstOrCreate(
                ['email' => Str::slug($name).'@pingmasters.test'],
                ['name' => $name, 'password' => Hash::make('password'), 'email_verified_at' => now(), 'club_id' => $club->id],
            );
            $user->assignRole('player');

            return Player::firstOrCreate(
                ['user_id' => $user->id],
                ['club_id' => $club->id, 'rating_current' => $rating],
            );
        };

        $divisionsSpec = [
            [
                'name' => 'Individual Masculino — Eliminación Directa',
                'category_type' => 'singles',
                'gender_category' => 'male',
                'format' => 'single_elimination',
                'entrants' => 8,
                'extra' => [],
                'play_round_1' => true,
            ],
            [
                'name' => 'Individual Femenino — Doble Eliminación',
                'category_type' => 'singles',
                'gender_category' => 'female',
                'format' => 'double_elimination',
                'entrants' => 8,
                'extra' => [],
            ],
            [
                'name' => 'Dobles Mixto — Todos contra Todos',
                'category_type' => 'doubles',
                'gender_category' => 'mixed',
                'format' => 'round_robin',
                'entrants' => 6,
                'extra' => [],
                'partners' => true,
            ],
            [
                'name' => 'Sub-21 — Sistema Suizo',
                'category_type' => 'singles',
                'gender_category' => 'open',
                'format' => 'swiss',
                'entrants' => 8,
                'extra' => ['swiss_rounds' => 4],
            ],
            [
                'name' => 'Veteranos — Grupos + Eliminación',
                'category_type' => 'singles',
                'gender_category' => 'open',
                'format' => 'group_knockout',
                'entrants' => 9,
                'extra' => ['group_size' => 3, 'advance_per_group' => 2],
            ],
        ];

        foreach ($divisionsSpec as $index => $spec) {
            $division = TournamentDivision::updateOrCreate(
                ['tournament_id' => $tournament->id, 'name' => $spec['name']],
                array_merge([
                    'category_type' => $spec['category_type'],
                    'gender_category' => $spec['gender_category'],
                    'format' => $spec['format'],
                    'best_of' => 5,
                    'points_to_win' => 11,
                    'seed_by_rating' => true,
                    'status' => 'pending_draw',
                    'display_order' => $index,
                ], $spec['extra']),
            );

            // Clean slate if this seeder has run before, so re-running it is idempotent.
            $division->matches()->delete();
            $division->rounds()->delete();
            $division->groups()->delete();
            $division->registrationDivisions()->delete();
            $division->update(['status' => 'pending_draw']);

            $rating = 1400;
            for ($i = 0; $i < $spec['entrants']; $i++) {
                $player = $makePlayer($rating);
                $rating -= random_int(15, 45);

                $registration = TournamentRegistration::firstOrCreate(
                    ['tournament_id' => $tournament->id, 'player_id' => $player->id],
                    ['status' => 'approved', 'submitted_at' => now(), 'reviewed_by' => $organizer->id, 'reviewed_at' => now()],
                );

                TournamentRegistrationDivision::create([
                    'tournament_registration_id' => $registration->id,
                    'tournament_division_id' => $division->id,
                    'partner_name' => $spec['partners'] ?? false ? $nextName() : null,
                    'seed_rating_snapshot' => $player->rating_current,
                ]);
            }

            app(BracketGeneratorFactory::class)->make($division)->generate($division);

            if ($spec['play_round_1'] ?? false) {
                $this->playFirstReadyRound($division);
            }
        }

        $this->command?->info('Demo tournament seeded: torneo-demostracion-ping-masters');
        $this->command?->info('Organizer login: organizador@pingmasters.test / password');
    }

    private function playFirstReadyRound(TournamentDivision $division): void
    {
        $scoring = app(MatchScoringService::class);

        $round1Matches = $division->matches()
            ->whereHas('round', fn ($q) => $q->where('round_number', 1))
            ->where('status', 'ready')
            ->get();

        foreach ($round1Matches as $match) {
            $scoring->startMatch($match, $match->entrant1_id);

            for ($g = 0; $g < 3; $g++) {
                for ($p = 0; $p < 11; $p++) {
                    $scoring->recordPoint($match, $match->entrant1_id);
                }
            }
        }
    }
}
