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
        $postFilters = $request->filters();

        $posts = Post::paginatedListWithFilters($postFilters)
            ->through(fn(Post $post): array => [
                'id' => $post->id,
                'content' => $post->content,
                'mood' => $post->mood?->value,
                'status' => $post->status?->value,
                'date' => $post->display_date,
                'time' => $post->display_time,
                'program' => $post->student?->program ?? '',
                'anonymous_name' => $post->student?->anonymous_name,
                'last_name' => $post->student?->last_name,
                'first_name' => $post->student?->first_name,
            ]);

        return Inertia::render('PostManagement/Index', [
            'posts' => $posts,
            'filters' => $postFilters,
        ]);
    }

    public function flag(Post $post): RedirectResponse
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

    public function unflag(Post $post): RedirectResponse
    {
        try {
            $this->service->unflagPost($post);
        } catch (DomainException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Post unflagged as safe content.');
    }
}