<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private const ASSIGNABLE_ROLES = ['super_admin', 'organizer', 'referee', 'player'];

    public function index(Request $request): Response
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $query = User::with(['roles', 'club'])->orderBy('name');

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        $users = $query->paginate(20)->withQueryString();
        $users->getCollection()->transform(fn (User $user) => [
            ...$user->toArray(),
            'roles' => $user->getRoleNames(),
        ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => $request->only('search'),
            'availableRoles' => self::ASSIGNABLE_ROLES,
        ]);
    }

    public function updateRoles(Request $request, User $user)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'roles' => 'array',
            'roles.*' => [Rule::in(self::ASSIGNABLE_ROLES)],
        ]);

        $newRoles = $validated['roles'] ?? [];

        $isSelf = $request->user()->id === $user->id;
        $losingSuperAdmin = $user->hasRole('super_admin') && ! in_array('super_admin', $newRoles, true);

        if ($isSelf && $losingSuperAdmin) {
            return back()->with('error', 'No puedes quitarte tu propio rol de super administrador.');
        }

        if ($losingSuperAdmin && Role::findByName('super_admin')->users()->count() <= 1) {
            return back()->with('error', 'Debe quedar al menos un super administrador en el sistema.');
        }

        $user->syncRoles($newRoles);

        return back()->with('success', 'Roles actualizados.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        if ($request->user()->id === $user->id) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        if ($user->hasRole('super_admin') && Role::findByName('super_admin')->users()->count() <= 1) {
            return back()->with('error', 'Debe quedar al menos un super administrador en el sistema.');
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado.');
    }

    public function resetPassword(Request $request, User $user)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $default = config('admin.default_reset_password');

        $user->update(['password' => Hash::make($default)]);

        return back()->with('success', "Contraseña de {$user->name} restablecida a: {$default}. Compártela para que la cambie al entrar.");
    }
}
