<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    // <<<<<<<<<<<< Read user(s) method
    public function index(Request $request)
    {
        try {
            $users = User::latest()->paginate(20);

            return view('admin.users.index', compact('users'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Impossible de charger les utilisateurs: '.$e->getMessage());
        }
    }

    // <<<<<<<<<<<<<<<<<<<< search user

    public function search(Request $request)
    {
        // if (empty($request->search) && empty($request->role) && empty($request->status)) {
        //    return back()->with('info', 'All search fields are empty.');
        // }
        $search = $request->input('search');
        $status = $request->input('status');
        $role = $request->input('role');

        try {
            $users = User::query()
                ->when(! empty($search), function ($query) use ($search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('id', 'like', "%{$search}%");
                    });
                })
                ->when(! empty($role), function ($query) use ($role) {
                    return $query->where('role', $role);
                })
                ->when($status, function ($query, $status) {
                    return $query->where('is_active', $status == 'active');
                })
                ->orderBy('id')
                ->paginate(20);

            if (! $users->count()) {
                return redirect()->back()->with('info', 'No users found matching your criteria.');
            }

            return view('admin.users.index', compact('users'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'impossible de charger les utilisateurs: '.$e->getMessage());
        }
    }
    // >>>>>>>>>>>>>>>>>>>> search user

    // <<<<<<<<<<<<<<<<<<<< Create user(s) method

    // public function create_page(){
    //     return view("admin.users.create");
    // }
    public function create(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => ['required', Password::min(8)],
                'is_active' => 'sometimes|boolean',
                'role' => 'required|string|in:student,admin,librarian',
            ]);

            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => $validated['is_active'] ?? true,
                'role' => $validated['role'],
            ]);

            return redirect()->route('admin.users.all')
                ->with('success', 'Utilisateur créé avec succès');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création de l\'utilisateur: '.$e->getMessage());
        }
    }

    // >>>>>>>>>>>>>>>>>>>> Create user(s) method

    public function update_page($id)
    {
        try {
            $user = User::findOrFail($id); // This fetches the user

            return view('admin.users.update', compact('user')); // Pass user to view
        } catch (\Exception $e) {
            return redirect()->route('admin.users.all')
                ->with('error', 'Utilisateur non trouvé: '.$e->getMessage());
        }
    }
    // Edit Users method

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $rules = [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'is_active' => 'sometimes|boolean',
        ];

        try {

            // Add password validation rules only if password change is attempted
            if ($request->filled('password') || $request->filled('current_password')) {
                $rules['current_password'] = 'required|string';
                $rules['password'] = ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'];
                $rules['password_confirmation'] = 'required|string';
            }

            $validated = $request->validate($rules);

            // Validate current password if password change is requested
            if ($request->filled('password') || $request->filled('current_password')) {
                if (! Hash::check($request->current_password, $user->password)) {
                    return redirect()->back()
                        ->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.'])
                        ->withInput();
                }
            }

            // Update only if the field exists in request and is different
            $updatableFields = ['first_name', 'last_name', 'email', 'is_active'];
            $changesMade = false;

            foreach ($updatableFields as $field) {
                if ($request->has($field)) {
                    $user->$field = $request->$field;
                    $changesMade = true;
                }
            }

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
                $changesMade = true;
            }

            if ($changesMade) {
                $user->save();

                return redirect()->route('admin.users.all')
                    ->with('success', "L'utilisateur a été mis à jour avec succès");
            }

            return redirect()->back()->with('info', 'No changes were made');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour de l\'utilisateur: '.$e->getMessage())
                ->withInput();
        }
    }

    public function delete($id) // Change parameter to $id for consistency with route
    {
        $authUser = Auth::user();
        $userToDelete = User::findOrFail($id);

        // Prevent self-deletion
        if ($userToDelete->id === $authUser->id) {
            return redirect()->back()
                ->with('error', 'Vous ne pouvez pas supprimer votre propre compte!');
        }

        // Protection against deleting admin accounts
        if ($userToDelete->role->value === UserRole::ADMIN->value) {
            return redirect()->back()
                ->with('error', 'Les comptes administrateurs ne peuvent pas être supprimés!');
        }

        try {
            $userToDelete->delete();

            return redirect()->route('admin.users.all')
                ->with('success', "L'utilisateur #{$id} a été supprimé avec succès");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression de l\'utilisateur: '.$e->getMessage());
        }
    }
}
