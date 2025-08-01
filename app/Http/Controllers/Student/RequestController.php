<?php

namespace App\Http\Controllers\Student;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookRequest;
use App\Models\RequestInfo;
use App\Models\Student;
use Error;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;

class RequestController extends Controller
{
    //
    public function index()
    {
        $user = Student::findOrFail(Auth::user()->id);

        // Get all book requests for the current student with their latest status
        $bookRequests = BookRequest::with(['book', 'latestRequestInfo'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('student.requests.index', compact('bookRequests'));
    }

    public function create(Request $req, $bookId)
    {
        $user = Auth::user();
        // $user = Auth::user();
        $book = Book::findOrFail($bookId);

        if (! Gate::allows('borrow_books', $book)) {
            return back()->with([
                'error' => 'Vous n\'êtes pas autorisé à emprunter ce livre.',
            ]);

        }

        DB::beginTransaction();

        try {
            $bookReq = BookRequest::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]);

            RequestInfo::create([
                'user_id' => $user->id,
                'request_id' => $bookReq->id,
                'status' => RequestStatus::PENDING,
            ]);

            DB::commit();

            return back()->with([
                'success' => 'Demande soumise avec succès.',
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();

            return back()
                ->with([
                    'error' => 'Erreur lors de la soumission de la demande.',
                ]);
        }
    }

    public function cancel(Request $req, $reqId)
    {
        $bookReq = BookRequest::findOrFail($reqId);
        $user = Student::findOrFail(Auth::user()->id);

        if (! Gate::allows('cancel_req', $bookReq)) {
            return back()->with([
                'error' => 'Vous n\'êtes pas autorisé à annuler cette demande de livre.',
            ]);
        }

        try {
            RequestInfo::create([
                'user_id' => $user->id,
                'request_id' => $bookReq->id,
                'status' => RequestStatus::CANCELED,
            ]);

            return back()->with([
                'success' => 'Demande annulée avec succès.',
            ]);

        } catch (\Throwable $th) {
            // throw $th;

            return back()
                ->with([
                    'error' => 'Erreur lors de l\'annulation de la demande.',
                ]);
        }
    }

    public function show(Request $req, $reqId)
    {
        //
        $user = Student::findOrFail(Auth::user()->id);
        $bookReq = BookRequest::with('latestRequestInfo')->findOrFail($reqId);

        if (! Gate::allows('show_req', $bookReq)) {
            return back()->with([
                'error' => 'Vous n\'êtes pas autorisé à consulter cette demande de livre.',
            ]);
        }

        try {
            $reqInfo = $bookReq->latestRequestInfo;
            if ($reqInfo == null) {
                throw new Error('request info not found');
            }

            return view('student.requests.show', compact('bookReq', 'reqInfo'));

        } catch (\Throwable $th) {
            return back()
                ->with([
                    'error' => 'Erreur lors de la récupération de la demande.',
                ]);
        }

    }
}
