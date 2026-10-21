<div>
    <!-- Simplicity is the essence of happiness. - Cedric Bledsoe -->
@extends('layouts.app')

@section('title', 'Gestion des utilisateurs')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-normal text-hydro-coral">Administration</p>
            <h1 class="mt-2 text-3xl font-semibold text-hydro-ink">Utilisateurs</h1>
            <p class="mt-2 text-sm text-slate-600">Comptes et niveaux d’accès.</p>
        </div>
        <p class="text-sm text-slate-600">{{ $users->total() }} compte(s)</p>
    </div>

    <div class="overflow-hidden rounded-sm border border-hydro-ink/10 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] border-collapse text-left text-sm">
                <thead class="bg-hydro-mist text-xs uppercase text-slate-600">
                    <tr>
                        <th class="px-5 py-4 font-semibold">Utilisateur</th>
                        <th class="px-5 py-4 font-semibold">Téléphone</th>
                        <th class="px-5 py-4 font-semibold">Rôle</th>
                        <th class="px-5 py-4 font-semibold">Accès</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-hydro-ink">{{ $user->name }}</p>
                                <p class="mt-1 text-slate-500">{{ $user->email }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $user->phone ?: '—' }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $user->role->label() }}</td>
                            <td class="px-5 py-4">
                                <form class="flex items-center gap-2" method="POST" action="{{ route('admin.users.role.update', $user) }}">
                                    @csrf
                                    @method('PUT')
                                    <label class="sr-only" for="role-{{ $user->id }}">Rôle de {{ $user->name }}</label>
                                    <select class="rounded-sm border border-slate-300 bg-white px-2 py-2 text-sm" id="role-{{ $user->id }}" name="role">
                                        @foreach ($roles as $role)
                                            @if ($user->id !== auth()->id() || $role === \App\UserRole::Administrator)
                                                <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <button class="rounded-sm border border-hydro-ink/15 px-3 py-2 text-xs font-semibold text-hydro-ink hover:border-hydro-teal hover:text-hydro-teal" type="submit">Appliquer</button>
                                </form>
                                @error('role') <p class="mt-2 text-xs text-red-700">{{ $message }}</p> @enderror
                            </td>
                        </tr>
                    @empty
                        <tr><td class="px-5 py-10 text-center text-slate-500" colspan="4">Aucun utilisateur trouvé.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
