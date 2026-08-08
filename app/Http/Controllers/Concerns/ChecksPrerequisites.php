<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Prerequisite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait ChecksPrerequisites
{
    /**
     * @return Prerequisite[]
     */
    protected function missingPrerequisites(Prerequisite ...$prerequisites): array
    {
        return array_values(array_filter($prerequisites, fn (Prerequisite $p) => ! $p->satisfied));
    }

    /**
     * Redirect back to wherever the user came from (e.g. a blocked create-tournament page)
     * after satisfying a prerequisite, instead of always bouncing to the template's own index.
     */
    protected function redirectAfterSave(Request $request, string $default, string $message, string $flashKey = 'success'): RedirectResponse
    {
        $redirectTo = $request->input('redirect_to');

        if (is_string($redirectTo) && str_starts_with($redirectTo, '/') && ! str_starts_with($redirectTo, '//')) {
            return redirect($redirectTo)->with($flashKey, $message);
        }

        return redirect($default)->with($flashKey, $message);
    }
}
