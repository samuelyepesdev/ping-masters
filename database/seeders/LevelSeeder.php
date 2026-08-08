<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [1, 'Iniciante', 0],
            [2, 'Aprendiz', 100],
            [3, 'Competidor', 250],
            [4, 'Competidor Avanzado', 500],
            [5, 'Avanzado', 900],
            [6, 'Experto', 1400],
            [7, 'Élite', 2000],
            [8, 'Maestro', 2800],
            [9, 'Gran Maestro', 3800],
            [10, 'Leyenda', 5000],
        ];

        foreach ($levels as [$number, $name, $xpRequired]) {
            Level::updateOrCreate(
                ['level_number' => $number],
                ['name' => $name, 'xp_required' => $xpRequired],
            );
        }
    }
}
