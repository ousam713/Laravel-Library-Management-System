<?php

namespace App\Http\Controllers\Librarian;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\BookRequest;
use App\Models\RequestInfo;
use App\Models\Student;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;

class RequestController extends Controller
{
    public function process(Request $req, $reqId)
    {

        try {
            // 1. Authorization check
            if (! Gate::allows('librarian')) { // Verify permission name
                return back()->with([
                    'error' => 'Vous n\'êtes pas autorisé à traiter cette demande.',
                ]);
            }

            // 2. Fetch the request with related book
            /** @var BookRequest $bookRequest */
            $bookRequest = BookRequest::with('book', 'user')->findOrFail($reqId);

            // 2. Validate input data
            $validatedData = $req->validate([
                'status' => ['required', new Enum(RequestStatus::class)],
            ]);
            $newStatusEnum = RequestStatus::from($validatedData['status']); // Convert to Enum

            $newStatusValue = $newStatusEnum->value;
            
            // 3. Get the current status
            $currentStatusEnum = $bookRequest->latestRequestInfo?->status;
            $currentStatusValue = $currentStatusEnum?->value;

            // 4. Prevent setting the same status
            if ($currentStatusValue && $currentStatusValue === $newStatusValue) {
                return back()->with([
                    'info' => 'La demande est déjà dans l\'état : '.ucfirst(get_request_status_text(RequestStatus::from($newStatusValue))),
                ]);
            }

            // 5. Define allowed status transitions
            $allowedTransitions = [
                RequestStatus::PENDING->value => [
                    RequestStatus::APPROVED->value,
                    RequestStatus::REJECTED->value,
                    RequestStatus::CANCELED->value,
                ],
                RequestStatus::APPROVED->value => [
                    RequestStatus::BORROWED->value,
                ],
                RequestStatus::BORROWED->value => [
                    RequestStatus::RETURNED->value,
                    RequestStatus::OVERDUE->value,
                ],
                RequestStatus::OVERDUE->value => [
                    RequestStatus::RETURNED->value,
                ],
            ];

            // 6. Check if the requested transition is allowed
            $isTransitionAllowed = isset($allowedTransitions[$currentStatusValue]) &&
                                   in_array($newStatusValue, $allowedTransitions[$currentStatusValue]);

            // 7. Handle transitions from terminal states or disallowed transitions
            if (! $isTransitionAllowed) {
                return back()->with([
                    'error' => "Transition d'état invalide de ". get_request_status_text(RequestStatus::from($currentStatusValue)) . " vers " .get_request_status_text(RequestStatus::from($newStatusValue))
                ]);
            }
            

            // 8. Student check 
            $student = Student::findOrFail($bookRequest?->user?->id);
            if (! Gate::allows('processe_req', [$student, $newStatusEnum])) { // Verify permission name
                return back()->with([
                    'error' => 'Cet étudiant n\'est pas autorisé à emprunter de livres.',
                ]);
            }

            // 8. Specific check: Before approving, ensure book copies are available
            if ($newStatusEnum === RequestStatus::APPROVED) {
                // Assuming 'available_copies' is a method on the Book model returning an integer
                $availableCopies = $bookRequest->book->available_copies();

                if ($availableCopies <= 0) {
                    return back()->with([
                        'error' => 'Impossible d\'approuver la demande. Aucune copie du livre n\'est actuellement disponible.',
                    ]);
                }
            }

            // 9. Create the new RequestInfo record
            RequestInfo::create([
                'user_id' => Auth::user()->id,
                'request_id' => $reqId,
                'status' => $newStatusEnum,
            ]);

            // 10. Success response
            return back()->with([
                'success' => 'Statut mis à jour avec succès vers '.ucfirst(get_request_status_text(RequestStatus::from($newStatusValue))),
            ]);

        } catch (ModelNotFoundException $e) {
            // 11. Handle case where request ID is invalid
            return back()->with([
                'error' => 'ID de demande invalide.',
            ]);
        } catch (\Exception $e) {
            // 12. Handle any other unexpected errors

            return back()->with([
                'error' => 'Une erreur est survenue lors de la mise à jour de la demande. Veuillez réessayer.',
            ]);
        }
    }

    public function index(Request $req)
    {
        //
        try {
            $requests = BookRequest::with( 'user', 'book')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
            // Get all possible statuses for the filter dropdown
            $statuses = collect(RequestStatus::cases())->filter(fn ($status) => $status->value !== 'canceled' && $status->value !== 'pending');

            return view('librarian.requests.index', compact('requests', 'statuses'));

        } catch (\Throwable $th) {
            return back()->with([
                'error' => 'Erreur lors de la récupération des demandes.',
            ]);

        }
    }

    public function showDetails(Request $request, $reqId)
    {
        try {
            $bookRequest = BookRequest::with([
                'book.authors',
                'book.categories',
                'user',
                'requestInfo.user',
                'latestRequestInfo',
            ])->findOrFail($reqId);

            $availableCopies = $bookRequest->book->available_copies(); // Call the method
            $bookData = $bookRequest->book->toArray();
            // Now add the calculated value
            $bookData['available_copies'] = $availableCopies;

            // Also get user and requestInfos as arrays if needed in the response structure
            $userData = $bookRequest->user->toArray();
            $latestInfoData = $bookRequest->latestRequestInfo?->toArray(); // Safe navigation

            $requestHistory = $bookRequest->requestInfo()->with('user')->orderBy('created_at', 'desc')->get();

            $returnDate = $bookRequest->return_date();

            return response()->json([
                'success' => true,
                // Send the modified book data
                'bookRequest' => array_merge($bookRequest->toArray(), ['book' => $bookData, 'user' => $userData, 'return_date' => $returnDate]),
                'reqInfo' => $latestInfoData,
                'requestHistory' => $requestHistory,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                // 'error' => $e->getMessage(), // Uncomment only for debugging
            ], 500);
        }
    }

    public function show(Request $req, $reqId)
    {
        try {
            $request = BookRequest::with('requestInfo.user', 'user', 'book')
                ->findOrFail($reqId);

            return view('librarian.requests.show', compact('request'));

        } catch (\Throwable $th) {
            return back()->with([
                'error' => 'Erreur lors de la récupération des informations de la demande.',
            ]);
        }
    }
}
