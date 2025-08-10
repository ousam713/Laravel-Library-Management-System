<?php

namespace App\Http\Controllers\Librarian;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookRequest;
use App\Models\User;

class LibrarianDashboardController extends Controller
{
    public function index()
    {
        try {
            // books statistics
            $total_books = count_total_books();
            $non_available_books = get_non_available_books();
            $available_books = $total_books - $non_available_books;

            // book requests statistics
            $request_statistics = get_requests_statics();

            // last 8 requests (for quick start part)
            $pending_requests = BookRequest::whereRelation('latestRequestInfo', 'status', RequestStatus::PENDING)->take(8)->get();

            $pending_requests_data = [];

            foreach ($pending_requests as $request) {
                $student = User::findOrFail($request->user_id, ['first_name', 'last_name']);
                $data = (object) [
                    'id' => $request->id,
                    'book_title' => Book::findOrFail($request->book_id)->title,
                    'user_name' => $student->first_name.' '.$student->last_name,
                    'date' => $request->created_at,
                ];

                array_push($pending_requests_data, $data);
            }

            return view('librarian.dashboard', compact(
                ['total_books', 'non_available_books',
                    'available_books', 'request_statistics',
                    'pending_requests_data']));

        } catch (\Throwable $th) {
            // return $th;
            // return view('auth.login')->with('error', 'Une erreur s\'est produit lors de chargement de Tableau de board');
            return $th;
        }
    }
}

