@extends('layouts.app')

@section('title', 'Tableau de bord')

<style>
    .multi-stats .stat-item {
        font-size: 0.85em;
    }

    .info-box-wrapper {
        position: relative;
    }

    .tag_style {
        background: #bdbdbd;
        border-radius: 15px;
        padding: 1px 5px;
        bottom: 5px;
        position: absolute;
        right: -8px;
        font-size: 0.8em;
    }
</style>

@section('content_header')

<div class="row d-flex">
    <!-- books statistics -->
    <div class="col-lg-4 col-6 d-flex">
        <div class="info-box-wrapper w-100 ">
            <x-adminlte-info-box title="" text="" icon="fas fa-book-open" theme="info" icon-theme="info" class="w-100">
                <x-slot name="description">
                    <div class="multi-stats mt-8">
                        <div class="stat-item">
                            <span><!--<i class="fas fa-book-open text-primary ml-2"></i>-->Nombre total de
                                livres:</span>
                            <strong class="float-right"> {{ $total_books }} </strong>
                        </div>
                        <div class="stat-item">
                            <span class="flex-fill"><!--<i class="fas fa-check-circle text-success"></i>-->Livre
                                disponibles:</span>
                            <strong class="float-right"> {{ $available_books }} </strong>
                        </div>
                        <div class="stat-item">
                            <span><!--<i class="fas fa-exclamation-triangle text-warning"></i>-->Livre réservés:</span>
                            <strong class="float-right"> {{ $non_available_books }} </strong>
                        </div>
                    </div>
                </x-slot>
            </x-adminlte-info-box>
        </div>
    </div>

    <!-- Request_info_1 -->
    <div class="col-lg-4 col-6 d-flex">
        <div class="info-box-wrapper w-100 h-100">
            <x-adminlte-info-box title="" text="" icon="fas fa-clipboard-list" theme="warning" icon-theme="warning"
                class="w-100">
                <x-slot name="description">
                    <div class="multi-stats mt-8">
                        <div class="stat-item">
                            <span><!--<i class="fas fa-clock text-warning"></i>-->Demandes acceptées</span>
                            <strong class="float-right"> {{ $request_statistics->approved_requests }} </strong>
                        </div>
                        <div class="stat-item">
                            <span><!--<i class="fas fa-check text-success"></i>-->Demandes refusées</span>
                            <strong class="float-right"> {{ $request_statistics->rejected_requests }} </strong>
                        </div>
                        <div class="stat-item">
                            <span style="color:#ffc107;">.</span>
                            <strong class="float-right"> {{ " "}}</strong>
                        </div>
                    </div>
                </x-slot>
            </x-adminlte-info-box>
            <span class="tag_style">30 derniers jours</span>
        </div>
    </div>

    <!-- Accpted_request_status  -->
    <div class="col-lg-4 col-6 d-flex">
        <div class="info-box-wrapper w-100">
            <x-adminlte-info-box title="" text="" icon="fas fa-info-circle" theme="success" icon-theme="success"
                class="w-100">
                <x-slot name="description">
                    <div class="multi-stats mt-8">
                        <div class="stat-item">
                            <span>Livres empruntés</span>
                            <strong class="float-right"> {{ $request_statistics->borrowed_books }}</strong>
                        </div>
                        <div class="stat-item">
                            <span>Livres en retard</span>
                            <strong class="float-right"> {{ $request_statistics->overdue_requests }}</strong>
                        </div>
                        <div class="stat-item">
                            <span>Livres retournés</span>
                            <strong class="float-right"> {{ $request_statistics->returned_books }}</strong>
                        </div>
                    </div>
                </x-slot>
            </x-adminlte-info-box>
            <span class="tag_style">30 derniers jours</span>
        </div>
    </div>
</div>

<div class="container-fluid" style="margin-top:25px">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-9 col-md-7 col-sm-12 mb-2">
            <h4>Demandes en attente</h4>

            <div class="table-responsive bg-white">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Id demande</th>
                            <th>Titre de livre</th>
                            <th>Etudiant</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pending_requests_data as $request)
                            <tr>
                                <td>
                                    <span class="badge bg-dark">{{ $request->id }}</span>
                                </td>
                                <td>
                                    <span>
                                        {{ $request->book_title}}
                                    </span>
                                </td>
                                <td>{{ $request->user_name }}</td>
                                <td>{{ $request->date }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                    {{-- change two forms to conferm the changes--}}
                                      {{-- Added class 'process-request-form' and data-action --}}
                                        <form action="{{ route('librarian.requests.process', $request->id) }}" method="POST"
                                            class="d-inline process-request-form" data-action="Approuver">
                                            @csrf
                                            <input type="hidden" name="status"
                                                value="{{ App\Enums\RequestStatus::APPROVED }}">
                                            <button type="submit" class="btn btn-success btn-sm"
                                                style='width:35px; margin-right: 3px;' title="Approuver">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        {{-- Added class 'process-request-form' and data-action --}}
                                        <form action="{{ route('librarian.requests.process', $request->id) }}" method="POST"
                                            class="d-inline process-request-form" data-action="Rejeter">
                                            @csrf
                                            <input type="hidden" name="status"
                                                value="{{ App\Enums\RequestStatus::REJECTED }}">
                                            <button type="submit" class="btn btn-danger btn-sm " style='width:35px;'
                                                title="Rejeter">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        <tr></tr>
                    </tbody>

                </table>
            </div>

            <div class="mt-2 mr-1 text-right">
                <a href="{{ route('librarian.requests.index') }}" class="btn btn-sm btn-primary"
                    style="font-weight:600; ;">
                    voir plus<i style='font-size:13px; vertical-align: middle' class='fas ml-1'>&#xf101;</i>
                </a>
            </div>

        </div>

        <!-- Quick action sidebar -->
        <div class="col-lg-3 col-md-5 col-sm-12 ">

            <div class="d-grid gap-3" style='width: 80%; margin: 0 auto;'>
                <h4>Démarrage rapide</h4>
                <!-- Action Buttons -->
                <div class="d-grid gap-2">
                    <x-adminlte-button theme="outline-info" icon="fas fa-plus-circle" label="Ajouter un livre"
                        class="btn-block" data-toggle="modal" data-target="#BookModal"/>

                    <x-adminlte-button theme="outline-secondary" icon="fas fa-user ml-2" label="Profil" class="btn-block"
                        onclick="window.location.href='{{ route('profile.show') }}'" />

                </div>
            </div>
        </div>
    </div>
</div>
<x-adminlte-modal id="BookModal" title="Ajouter un livre" theme="info" icon="fas fa-plus">
    <form id="customForm" action="{{ route('librarian.books.isbn.getInfo') }}" method="GET">
        @csrf

        <div class="form-group">
            <label for="item_name">ISBN du livre</label>
            <div class="input-group">
                <input type="text"
                       class="form-control"
                       id="item_name"
                       name="isbn"
                       placeholder="Saisir ici ..."
                       required>
                <div class="input-group-append">
                    <button type="submit" class="btn" style="background-color:#00b6d3;">
                        <i class="fas fa-magic"></i> Ajouter
                    </button>
                </div>
            </div>
            <small class="form-text text-muted">
                Ce livre sera recherché via l'API Google Books
            </small>
        </div>
    </form>

    <hr class="my-3">

    <div class="text-center">
        <p class="text-muted mb-3">Ou choisissez une autre manière :</p>
        <a href="{{route('librarian.books.create')}}">
            <button type="button" class="btn btn-secondary btn-lg btn-block" >
                <i class="fas fa-pen"></i> Ajouter le livre manuellement
            </button>
        </a>
    </div>

    <x-slot name="footerSlot">
        <button type="button" class="btn btn-default" data-dismiss="modal">
            <i class="fas fa-times"></i> Annuler
        </button>
    </x-slot>
</x-adminlte-modal>

@stop
@section('js')
    @parent {{-- Ensure parent JS is included if any --}}
    <script>
        $(document).ready(function() {
            // SweetAlert for processing requests (Approve/Reject)
            $('.process-request-form').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission
                var form = this;
                var actionType = $(form).data('action'); // Get the action type (Approuver/Rejeter)

                Swal.fire({
                    title: 'Confirmer l\'action?',
                    text: `Êtes-vous sûr de vouloir ${actionType.toLowerCase()} cette demande?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: `Oui, ${actionType.toLowerCase()}!`,
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit(); // Submit the form if confirmed
                    }
                });
            });
        });
    </script>
@stop
