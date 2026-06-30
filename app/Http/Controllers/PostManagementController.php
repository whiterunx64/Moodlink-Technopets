<?php

namespace App\Http\Controllers;

use App\Exceptions\PostModerationException;
use App\Http\Requests\PostManagementFilterRequest;
use App\Models\Post;
use App\Services\PostManager;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PostManagementController extends Controller
{
    public function __construct(
        private readonly PostManager $service,
    ) {
    }

    public function index(PostManagementFilterRequest $request): Response
    {
        $postFilters = $request->filters();

        return Inertia::render('PostManagement/Index', [
            'posts' => $this->service->paginatedPostList($postFilters),
            'filters' => $postFilters,
            'counts' => $this->service->statusCounts(),
        ]);
    }

    public function flag(Post $post): RedirectResponse
    {
        try {
            $this->service->flagPost($post);
        } catch (PostModerationException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with(
            'flash_success',
            'Post flagged as potentially inappropriate content.'
        );
    }

    public function unflag(Post $post): RedirectResponse
    {
        try {
            $this->service->unflagPost($post);
        } catch (PostModerationException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Post unflagged as safe content.');
    }
}