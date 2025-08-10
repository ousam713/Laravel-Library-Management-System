@php
use App\Enums\RequestStatus;
@endphp

@extends('admin.dashboard')

@section('css')
<style>
    .scrollable-table-container {
        max-height: 45%;  /* Adjust height as needed */
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    .sticky-header th {
        position: sticky;
        top: 0;
        background: #343a40; /* Matches thead-dark */
        color: white;
        z-index: 10;
    }
</style>
@endsection

@section('content')
    <div class="container">
        <h1>Les étudiants</h1>

        {{-- Search and filter --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Chercher des étudiants</h5>
            </div>
            <div class="card-body">
                <div class="container col-11">
                    <form action="{{ route('admin.statistics.users.search') }}" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">critères de recherches</label>
                            <input type="text" class="form-control" id="search" name="search"
                                placeholder="Chercher par id, nom, email, etc..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-4">
                            <label for="activity" class="form-label">Dernière activité</label>
                            <select class="form-select" id="activity" name="activity" style="cursor: pointer;">
                                <option value="">Tous les statuts</option>
                                @foreach(App\Enums\RequestStatus::cases() as $activity)
                                    <option value="{{ $activity->value }}" {{ request('activity') == $activity->value ? 'selected' : '' }}>
                                        @switch($activity)
                                            @case(App\Enums\RequestStatus::PENDING) En attente @break
                                            @case(App\Enums\RequestStatus::BORROWED) Emprunté @break
                                            @case(App\Enums\RequestStatus::APPROVED) Approuvé @break
                                            @case(App\Enums\RequestStatus::REJECTED) Rejeté @break
                                            @case(App\Enums\RequestStatus::OVERDUE) En retard @break
                                            @case(App\Enums\RequestStatus::RETURNED) Rendu @break
                                        @endswitch
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Statut</label>
                            <select class="form-select" id="status" name="status" style="cursor: pointer;">
                                <option value="">Tout les status</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactif</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i> Chercher
                            </button>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <a href="{{ route('admin.statistics.users') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times me-2"></i> Effacer
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if(request()->hasAny(['search', 'role', 'status']))
            <div class="alert alert-info mb-3">
                Afficher les resultats pour:
                @if(request('search')) <strong> {{ request('search') }} </strong> @endif
                @if(request('role')) <strong> {{ ucfirst(request('role')) }} </strong>@endif
                @if(request('status')) <strong> {{ ucfirst(request('status')) }} </strong>@endif
                <a href="{{ route('admin.statistics.users') }}" class="float-end">Afficher tout</a>
            </div>
        @endif
        {{-- Search and filter --}}

        {{-- Scrollable table container --}}
        <div class="scrollable-table-container">
            <table class="table table-striped table-hover">
                <thead class="thead-dark sticky-header">
                    <tr>
                        <th>Id</th>
                        <th>Prénom</th>
                        <th>Nom</th>
                        <th>E-mail</th>
                        <th>Rôle</th>
                        <th>Dernière activité</th>
                        <th>Titre du livre</th>
                        <th style="text-align: center;">Statut</th>
                        <th style="text-align: center;">Historique</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                            @php
                                $latestInfo = null;
                                if ($user->bookRequests && $user->bookRequests->isNotEmpty()) {
                                    $latestRequest = $user->bookRequests->first();
                                    $latestInfo = $latestRequest->latestRequestInfo ?? null;
                                }
                            @endphp
                                {{-- @if ($user->role->value === 'student') --}}
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
                                <td>
                                    @if($latestInfo)
                                        @php
                                            $status = $latestInfo->status;
                                            $bgColor = get_request_status_badge($status);
                                            $badgeText = get_request_status_text($status);
                                        @endphp

                                        <span class="badge bg-{{ $bgColor }}"> {{$badgeText}} </span>
                                        <small>({{ "il y'a " . str_replace([' hours ago', 'hour ago', ' day ago','ago','week'], ['h', 'h', 'j','','semaine'], $latestInfo?->created_at->diffForHumans()) }}) </small>
                                        
                                    @else
                                        <span class="badge bg-secondary">pas d'activié</span>
                                    @endif
                                </td>
                                <td>{{$latestRequest->book?->title ?? 'N/A'}}</td>
                                <td style="text-align: center;">
                                    <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">
                                        {{ $user->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.statistics.users.history', $user->id) }}"
                                        class="btn btn-secondary btn-sm"
                                        title="View History">
                                            <i class="fas fa-history"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        {{-- @endif --}}
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <a href="{{ route('admin.statistics.users.export') }}" class="btn btn-success mr-2">
                <i class="fas fa-file-excel mr-2"></i> Exporter
            </a>
        </div>

        {{-- Keep pagination for navigation --}}
        {{ $users->withQueryString()->links('pagination::bootstrap-5') }}
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection
