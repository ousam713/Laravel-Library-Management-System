<?php

namespace App\Providers;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\BookRequest;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Gate::define('borrow_books', function (?User $user, Book $book) {
            // Convert User to Student if needed
            if ($user instanceof User && ! ($user instanceof Student)) {
                $user = Student::find($user->id);
            }

            if (! $user || ! ($user instanceof Student)) {
                return false;
            }

            return $user->is_active &&
                   $book->total_copies > get_borrowed_copies($book->id) &&
                   $user->get_totale_borrowed_books() < Setting::find(1)?->NOMBRE_EMPRUNTS_MAX;
        });

        Gate::define('cancel_req', function (User $user, BookRequest $req) {
            return $user->is_active && $user->id == $req->user_id && get_latest_info($req->id)->status == RequestStatus::PENDING;
        });

        Gate::define('show_req', function (User $user, BookRequest $req) {
            return $user->is_active && $user->id == $req->user_id;
        });

        Gate::define('processe_req', function (User $user, Student $student, RequestStatus $status) {
            // Only check borrowed books limit for APPROVED status
            if (in_array($status, [RequestStatus::APPROVED])) {
                return $student->get_totale_borrowed_books() < Setting::find(1)?->NOMBRE_EMPRUNTS_MAX;
            }

            // For other statuses (REJECTED, RETURNED, etc.), just allow if user is active librarian
            return true;
        });

        Gate::define('student', function (User $user) {
            return $user->is_active && $user->role === UserRole::STUDENT;
        });
        Gate::define('librarian', function (User $user) {
            return $user->is_active && $user->role === UserRole::LIBRARIAN;
        });
        Gate::define('admin', function (User $user) {
            return $user->is_active && $user->role === UserRole::ADMIN;
        });

    }
}
