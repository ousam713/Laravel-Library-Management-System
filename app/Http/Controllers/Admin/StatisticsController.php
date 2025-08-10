<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Exports\BooksExport;
use App\Exports\LibrariansExport;
use App\Exports\StudentsExport;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookRequest;
use App\Models\Librarian;
use App\Models\RequestInfo;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StatisticsController extends Controller
{
        public function dashboard(){
        return view('admin.statistics.index');
    }


    public function users_stat(Request $request)
    {
        try {
            // Get students with their latest book request and related info
            $students = Student::with(['bookRequests' => function($query) {
                    $query->with(['latestRequestInfo', 'book'])
                        
                        ->limit(1);
                }])
                ->latest()
                ->paginate(22);

            return view('admin.statistics.users', ['users' => $students]);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors du chargement des données des étudiants : ' . $e->getMessage());
        }
}

    public function search(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $activity = $request->input('activity');

        $users = Student::query()
            ->with(['bookRequests' => function($query) {
                $query->with(['latestRequestInfo', 'book'])
                    ->latest()
                    ->limit(1);
            }])
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
                });
            })
            ->when($status, function ($query, $status) {
                return $query->where('is_active', $status == 'active');
            })
            ->when($activity, function ($query, $activity) {
                return $query->whereHas('bookRequests.latestRequestInfo', function($q) use ($activity) {
                    $q->where('status', RequestStatus::from($activity));
                });
            })
            ->orderBy('id')
            ->paginate(20);

            if(!$users->count()) {
                return redirect()->back()->with('info', 'Aucun utilisateur trouvé correspondant à vos critères.');
            }
        return view('admin.statistics.users', compact('users'));
}

        public function search_librarian(Request $request)
    {
        try{
            $search = $request->input('search');
            $status = $request->input('status');

            $users = Librarian::query()
                ->when($search, function ($query, $search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%");
                    });
                })
                ->when($status, function ($query, $status) {
                    return $query->where('is_active', $status == 'active');
                })
                ->orderBy('id')
                ->paginate(5);

                if(!$users->count()) {
                    return redirect()->back()->with('info', 'Aucun utilisateur trouvé correspondant à vos critères.');
                }
            return view('admin.statistics.librarian', compact('users'));
            }catch (\Exception $e) {
                return redirect()->back()
                    ->with('error', 'Erreur lors du chargement des données des bibliothécaires: ' . $e->getMessage());
            }
}

    public function books_stat(Request $request)
    {
        // try {
        $books = Book::latest()->paginate(20);

        return view('admin.statistics.books', compact('books'));

    }

    public function search_book(Request $request)
    {
        try {
            $query = $request->input('search');

            $books = Book::where('title', 'like', "%{$query}%")
                ->orWhere('isbn', 'like', "%{$query}%")
                ->paginate(20);

            if (! $books->count()) {
                return redirect()->back()->with('info', 'Aucun livre trouvé correspondant à vos critères.');
            }

            return view('admin.statistics.books')->with('books', $books);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors du chargement des données des livres: '.$e->getMessage());

        }
    }

    public function librarian_stat(Request $request)
    {
        try {
            $users = User::where('role', 'librarian')->paginate(15);

            return view('admin.statistics.librarian', compact('users'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Impossible de charger les utilisateurs: '.$e->getMessage());
        }

    }

    public function exportStudents()
    {
        $studentsQuery = User::where('role', UserRole::STUDENT->value)
            ->orderBy('last_name')
            ->orderBy('first_name');

        return Excel::download(new StudentsExport($studentsQuery), 'students_'.now()->format('Y-m-d').'.xlsx');
    }

    public function exportLibrarians()
    {
        $query = User::where('role', UserRole::LIBRARIAN->value)
            ->orderBy('last_name')
            ->orderBy('first_name');

        return Excel::download(
            new LibrariansExport($query),
            'librarians_'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function exportBooks()
    {
        $query = Book::query()
            ->orderBy('title')
            ->select(['title', 'isbn', 'number_of_pages', 'total_copies']);

        return Excel::download(
            new BooksExport($query),
            'books_export_'.now()->format('Y-m-d').'.xlsx'
        );
    }

 public function user_history(Student $user)
{
    $requestInfos = RequestInfo::with(['bookRequest.book', 'bookRequest.user', 'user'])
        ->whereHas('bookRequest', fn($q) => $q->where('user_id', $user->id))
        ->orderBy('created_at', 'desc')
        ->paginate(5)
        ->through(fn($requestInfo) => $this->formatRequestInfo($requestInfo));

    // Marquer la première demande
    if ($requestInfos->isNotEmpty()) {
        $requestInfos->first()['is_first'] = true;
    }

    return view('admin.statistics.users_history', [
        'user' => $user,
        'requests' => $requestInfos,
        'totalRequests' => $requestInfos->total(),
    ]);
}

private function formatRequestInfo($requestInfo)
{
    $request = $requestInfo->bookRequest;
    $isPending = $requestInfo->status === 'pending';
    
    // Déterminer le bibliothécaire
    $librarian = 'En attente de traitement';
    $processed_at = null;
    
    if (!$isPending && $requestInfo->user?->role->value === UserRole::LIBRARIAN->value) {
        $librarian = $requestInfo->user->first_name . ' ' . $requestInfo->user->last_name;
        $processed_at = $requestInfo->created_at;
    }

    return [
        'id' => $request->id,
        'request_info_id' => $requestInfo->id,
        'created_at' => $request->created_at,
        'book_title' => $request->book->title ?? 'N/A',
        'status' => $requestInfo->status,
        'processed_at' => $processed_at,
        'processed_by' => $librarian,
        'created_diff' => "il y'a " . str_replace([' hours ago', 'hour ago', 'ago','week'], ['h','h','','semaine'], $requestInfo->created_at->diffForHumans()),
        'processed_diff' => $requestInfo->created_at?->diffForHumans(),
        'action_date' => $requestInfo->created_at,
        'action_date_formatted' => $requestInfo->created_at->format('d/m/Y H:i'),
        'original_request_date' => $request->created_at->format('d/m/Y H:i'),
        'is_first' => false,
    ];
}

public function librarian_history(Librarian $user)
{
    // Get all RequestInfo records created by this librarian
    $requestInfos = \App\Models\RequestInfo::with(['bookRequest.book', 'bookRequest.user', 'user'])
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->paginate(6)
        ->through(function ($requestInfo) {
            $request = $requestInfo->bookRequest;
            
            return [
                'id' => $request->id,
                'request_info_id' => $requestInfo->id,
                'created_at' => $requestInfo->created_at,
                'created_diff' => "il y'a ".str_replace([' hours ago', 'hour ago', ' day ago','ago','week'], ['h', 'h', 'j','','semaine'], $requestInfo->created_at->diffForHumans()),
                'response_date' => $requestInfo->created_at,
                'book_title' => $request->book->title ?? 'N/A',
                'status' => $requestInfo->status,
                'requested_at' => $request->created_at->format('d/m/Y H:i'),
                'requested_by' => $request->user->first_name.' '.$request->user->last_name,
                'librarian' => $requestInfo->user->first_name.' '.$requestInfo->user->last_name,
                'action_date' => $requestInfo->created_at->format('d/m/Y H:i'),
                'is_first' => false,
            ];
        });

    // Mark the first request for special display
    if ($requestInfos->count() > 0) {
        $requestInfos->first()['is_first'] = true;
    }

    return view('admin.statistics.librarian_history', [
        'user' => $user,
        'requests' => $requestInfos,
        'totalRequests' => $requestInfos->total(),
    ]);
}

    // app/Http/Controllers/BookController.php
    public function history(Book $book)
    {
        $borrowings = BookRequest::with(['user', 'requestInfo.user'])
            ->where('book_id', $book->id)
            ->whereHas('requestInfo', function ($query) {
                $query->where('status', 'borrowed');
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->through(function ($request) {
                $borrowInfo = $request->requestInfo
                    ->where('status', 'borrowed')
                    ->sortByDesc('created_at')
                    ->first();

                $returnInfo = $request->requestInfo
                    ->where('status', 'returned')
                    ->sortByDesc('created_at')
                    ->first();

                $librarian_returned = 'non retourner';

                if($returnInfo){
                    $librarian_returned = $returnInfo->user->first_name.' '.$returnInfo->user->lastname;
                }

                return [
                    'user_name' => $request->user->first_name.' '.$request->user->last_name,
                    'borrow_date' => $borrowInfo->created_at->format('Y-m-d H:i'),
                    'librarian_borrowed' => $borrowInfo->user->first_name.' '.$borrowInfo->user->first_name ?? "n'est pas emprunté",
                    'return_date' => $returnInfo ? $returnInfo->created_at->format('Y-m-d H:i') : null,
                    'librarian_returned' => $librarian_returned,
                    'is_returned' => ! is_null($returnInfo),
                    'duration' => $returnInfo
                        ? $borrowInfo->created_at->diffForHumans($returnInfo->created_at, true)
                        : $borrowInfo->created_at->diffForHumans(now(), true),
                ];
            });

        return view('admin.statistics.book_history', [
            'book' => $book,
            'borrowings' => $borrowings,
        ]);
    }
}
