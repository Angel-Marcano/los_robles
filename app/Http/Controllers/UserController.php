<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tower;
use App\Models\Apartment;
use App\Models\Ownership;
use App\Models\Tenant\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller {
    public function index(){
        $users = User::with('roles', 'towers', 'ownerships.apartment.tower')->paginate(10);
        return view('users.index', compact('users'));
    }

    public function create(){
        $roles = Role::orderBy('name')->get();
        $towers = Tower::with('apartments')->orderBy('name')->get();
        return view('users.create', compact('roles', 'towers'));
    }

    public function store(Request $r){
        $data = $r->validate([
            'name'             => 'required',
            'first_name'       => 'required|string|max:80',
            'last_name'        => 'required|string|max:120',
            'document_type'    => 'required|in:cedula,pasaporte',
            'document_number'  => 'required|string|max:40',
            'phone'            => 'sometimes|nullable|string|max:30',
            'email'            => 'required|email|unique:users',
            'password'         => 'required|min:6',
            'roles'            => 'sometimes|array',
            'roles.*'          => 'string|exists:roles,name',
            'tower_ids'        => 'sometimes|array',
            'tower_ids.*'      => 'integer|exists:towers,id',
            'apartment_ids'    => 'sometimes|array',
            'apartment_ids.*'  => 'integer|exists:apartments,id',
            'ownership_role'   => 'sometimes|in:owner,co_owner,tenant',
        ]);
        $data['password'] = Hash::make($data['password']);
        $roles = $data['roles'] ?? [];
        $towerIds = $data['tower_ids'] ?? [];
        $apartmentIds = $data['apartment_ids'] ?? [];
        $ownershipRole = $data['ownership_role'] ?? 'owner';
        unset($data['roles'], $data['tower_ids'], $data['apartment_ids'], $data['ownership_role']);

        $user = User::create($data);
        if (!empty($roles)) {
            $user->assignRole($roles);
        }
        if (!empty($towerIds)) {
            $user->towers()->sync($towerIds);
        }
        if (!empty($apartmentIds)) {
            foreach ($apartmentIds as $aptId) {
                Ownership::create([
                    'user_id'      => $user->id,
                    'apartment_id' => $aptId,
                    'role'         => $ownershipRole,
                    'active'       => true,
                ]);
            }
        }
        return redirect()->route('users.index')->with('status', 'Usuario creado correctamente');
    }

    public function edit(User $user){
        $roles = Role::orderBy('name')->get();
        $towers = Tower::with('apartments')->orderBy('name')->get();
        $user->load('towers', 'ownerships.apartment.tower');
        $currentAptIds = $user->ownerships->pluck('apartment_id')->toArray();
        $currentOwnershipRole = $user->ownerships->first()?->role;
        return view('users.edit', compact('user', 'roles', 'towers', 'currentAptIds', 'currentOwnershipRole'));
    }

    public function update(Request $r, User $user){
        $data = $r->validate([
            'name'             => 'sometimes|string|max:120',
            'first_name'       => 'sometimes|string|max:80',
            'last_name'        => 'sometimes|string|max:120',
            'document_type'    => 'sometimes|nullable|in:cedula,pasaporte',
            'document_number'  => 'sometimes|nullable|string|max:40',
            'phone'            => 'sometimes|nullable|string|max:30',
            'email'            => 'sometimes|email|unique:users,email,'.$user->id,
            'password'         => 'sometimes|nullable|min:6',
            'active'           => 'sometimes|boolean',
            'roles'            => 'sometimes|array',
            'roles.*'          => 'string|exists:roles,name',
            'tower_ids'        => 'sometimes|array',
            'tower_ids.*'      => 'integer|exists:towers,id',
            'apartment_ids'    => 'sometimes|array',
            'apartment_ids.*'  => 'integer|exists:apartments,id',
            'ownership_role'   => 'sometimes|in:owner,co_owner,tenant',
        ]);
        if (array_key_exists('password', $data) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        if (array_key_exists('active', $data)) {
            $data['active'] = (bool) $data['active'];
        }
        $roles = $data['roles'] ?? null;
        $towerIds = $data['tower_ids'] ?? null;
        $apartmentIds = $data['apartment_ids'] ?? null;
        $ownershipRole = $data['ownership_role'] ?? 'owner';
        unset($data['roles'], $data['tower_ids'], $data['apartment_ids'], $data['ownership_role']);

        $user->update($data);
        if ($roles !== null) {
            $user->syncRoles($roles);
        }
        if ($towerIds !== null) {
            $user->towers()->sync($towerIds);
        }
        if ($apartmentIds !== null) {
            Ownership::where('user_id', $user->id)
                ->whereNotIn('apartment_id', $apartmentIds)
                ->delete();
            foreach ($apartmentIds as $aptId) {
                Ownership::updateOrCreate(
                    ['user_id' => $user->id, 'apartment_id' => $aptId],
                    ['role' => $ownershipRole, 'active' => true]
                );
            }
        }
        return back()->with('status', 'Usuario actualizado');
    }

    public function destroy(User $user){
        $user->delete();
        return back();
    }

    public function toggle(User $user){
        $user->update(['active' => !$user->active]);
        return back();
    }
}
