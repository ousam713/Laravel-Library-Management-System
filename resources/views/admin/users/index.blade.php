@extends('admin.dashboard')

@section('css')
<style>
    .table-container {
        max-height: 370px;
        overflow-y: auto;
        margin-bottom: 20px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
    }
    .table-container table {
        margin-bottom: 0;
    }
    .sticky-header th {
        position: sticky;
        top: 0;
        background: white;
        z-index: 10;
        box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1);
    }
</style>
@endsection

@section('content')
<div class="container mb-4">
    <h1>Étudiants</h1>
        
    {{-- Search and filter --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Chercher des étudiants</h5>
        </div>
        <div class="card-body">
            <div class="container col-11">
                <form action="{{ route('admin.users.search') }}" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Teremes de recherches</label>
                        <input type="text" class="form-control" id="search" name="search" 
                            placeholder="Chercher par id, nom, email, etc..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="role" class="form-label">Rôle</label>
                        <select class="form-select" id="role" name="role" style="cursor: pointer;">
                            <option value="">Tous les rôles</option>
                            <option value="student" {{ request('role') == 'student' ? 'selected' : '' }}>Étudiant</option>
                            <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="librarian" {{ request('role') == 'librarian' ? 'selected' : '' }}>Bibliothecaires</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Statut</label>
                        <select class="form-select" id="status" name="status" style="cursor: pointer;">
                            <option value="">Tout les statuts</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactif</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i> Search
                        </button>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <a href="{{ route('admin.users.all') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times me-2"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Create user --}}
    <div class="col-md-6 d-flex align-items-end mb-3">
        <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#createUserModal">
            <i class="fa-solid fa-user-plus me-2"></i> Créer un utilisateur
        </a>
    </div>

    {{-- Search results info --}}
    @if(request()->hasAny(['search', 'role', 'status']))
        <div class="alert alert-info mb-3">
            @php
                $search = request('search');
                $role = request('role');
                $status = request('status');
            @endphp
            Affichage des résultats pour:
            @if(request('search')) <strong>Recherche:</strong> {{ request('search') }} @endif
            @if(request('role')) <strong>Rôle:</strong> 
                @if ($role === 'student')
                    Étudiant
                @elseif ($role === 'admin')
                    admin
                @elseif ($role === 'librarian')
                    bibliothécaire
                @endif
            @endif
            @if(request('status')) <strong>Statut:</strong> {{ ucfirst(request('status')) }} @endif
            <a href="{{ route('admin.users.all') }}" class="float-end">Afficher tout</a>
        </div>
    @endif

    {{-- Users table with scroll --}}
    <div class="table-container">
        <table class="table table-striped table-hover">
            <thead class="sticky-header">
                <tr>
                    <th>Id</th>
                    <th>Prénom</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th style="text-align: center;">Statut</th>
                    <th style="text-align: center;">Modifier</th>
                    <th style="text-align: center;">Supprimer</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->first_name }}</td>
                        <td>{{ $user->last_name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if ($user->role->value === 'student')
                                <p class="mb-0">étudiant</p>
                            @elseif ($user->role->value === 'admin')
                                <p class="mb-0">admin</p>
                            @elseif ($user->role->value === 'librarian')
                                <p class="mb-0">bibliothécaire</p>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <a href="{{ route('admin.users.update', $user->id) }}" 
                               class="btn btn-warning btn-sm" 
                               title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                        <td style="text-align: center;">
                            <form action="{{ route('admin.users.delete', $user->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" 
                                        onclick="return confirm('Are you sure you want to delete this user?')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div >
        {{ $users->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
</div>
@include('admin.users.modal.create')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection