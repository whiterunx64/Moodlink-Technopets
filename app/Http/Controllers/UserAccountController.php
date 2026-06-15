<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterStudentRequest;
use App\Http\Requests\UpdateStudentStatusRequest;
use App\Http\Requests\UserAccountFilterRequest;
use App\Models\Student;
use App\Services\StudentAccountService;
use DomainException;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserAccountController extends Controller
{
    public function __construct(
        private readonly StudentAccountService $service,
    ) {
    }

    /**
     * Display paginated user accounts with filters and tab counts.
     */
    public function index(UserAccountFilterRequest $request): Response
    {
        $filters = $request->filters();

        $students = $this->paginateStudentAccounts($filters);

        $tabCounts = Student::countsByTab($filters);

        return Inertia::render('UserAccounts/Index', [
            'students' => $students,
            'tabCounts' => $tabCounts,
            'filters' => $filters,
        ]);
    }

    public function updateStatus(
        UpdateStudentStatusRequest $request,
        Student $student,
    ): RedirectResponse {
        $targetStatus = $request->targetStatus();

        try {
            $this->service->changeStudentStatus(
                student: $student,
                targetStatus: $targetStatus,
            );
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return back();
    }

    /**
     * Register a Supabase account for a student.
     *
     * @throws DomainException
     * @throws Exception
     */
    public function register(
        RegisterStudentRequest $request,
        Student $student,
    ): RedirectResponse {
        $validated = $request->validated();

        $email = $validated['email'];
        $password = $validated['password'];

        try {
            $this->service->createSupabaseAccountForStudent(
                student: $student,
                email: $email,
                password: $password,
            );
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        } catch (Exception $exception) {
            return back()->withErrors([
                'email' => 'Supabase registration failed: ' . $exception->getMessage(),
            ]);
        }

        return back();
    }

    /**
     * Paginate filtered student accounts for admin listing.
     */
    private function paginateStudentAccounts(array $filters): LengthAwarePaginator
    {
        $query = Student::queryWithFiltersAndTab($filters)
            ->orderBy('last_name')
            ->orderBy('first_name');

        $paginator = $query->paginate(Student::ADMIN_PAGE_SIZE);

        $paginator->withQueryString();

        $paginator->through(
            fn(Student $student) => $student->toListRow()
        );

        return $paginator;
    }
}