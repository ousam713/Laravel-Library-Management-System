<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    //
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
            return view('admin.dashboard_page',compact(
                ['total_books', 'non_available_books',
                    'available_books', 'request_statistics',
                    'pending_requests_data']));
            }catch (\Throwable $th) {
            return view('auth.login')->with('error', 'Une erreur s\'est produit lors de chargement de Tableau de board');
        }
    }

    public function profile()
    {
        try {
            // Get the currently authenticated user
            $user = Auth::user();

            return view('admin.profile', compact('user'));
        } catch (\Exception $e) {
            return redirect()->route('admin.dashboard')
                ->with('error', $e->getMessage());
        }

    }

    public function all_requests(Request $req)
    {
        //
        try {
            $requests = BookRequest::with('requestInfo', 'user', 'book')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
            // Get all possible statuses for the filter dropdown
            $statuses = collect(\App\Enums\RequestStatus::cases())->filter(fn ($status) => $status->value !== 'canceled' && $status->value !== 'pending');

            return view('admin.requests.index', compact('requests', 'statuses'));

        } catch (\Throwable $th) {
            return back()->with(['error' => 'Error while fetching requests']);

        }
    }

    public function show(Request $req, $reqId)
    {
        try {
            $request = BookRequest::with('requestInfo.user', 'user', 'book')
                ->findOrFail($reqId);

            return view('admin.requests.show', compact('request'));

        } catch (\Throwable $th) {
            return back()->with(['error' => 'Error while fetching the request information']);
        }
    }
}