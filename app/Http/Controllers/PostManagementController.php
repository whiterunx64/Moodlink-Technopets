<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostManagementFilterRequest;
use App\Models\Post;
use App\Services\PostManagementService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PostManagementController extends Controller
{
    public function __construct(
        private readonly PostManagementService $service,
    ) {
    }

    public function index(PostManagementFilterRequest $request): Response
    {
        $filters = $request->filters();

        $posts = Post::paginatedListWithFilters($filters);

        return Inertia::render('PostManagement/Index', [
            'posts' => $posts,
            'filters' => $filters,
        ]);
    }

    public function flagPost(Post $post): RedirectResponse
    {
        try {
            $this->service->flagPost($post);
        } catch (DomainException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with(
            'flash_success',
            'Post flagged as potentially inappropriate content.'
        );
    }

    public function unflagPost(Post $post): RedirectResponse
    {
        try {
            $this->service->unflagPost($post);
        } catch (DomainException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Post unflagged.');
    }
}
