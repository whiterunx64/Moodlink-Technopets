<?php

namespace App\Http\Controllers;

use App\Exceptions\PostModerationException;
use App\Http\Requests\PostManagementFilterRequest;
use App\Models\PendingPost;
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
        $tab = $postFilters['tab'];

        return Inertia::render('PostManagement/Index', [
            'posts'         => $this->service->paginatedPostList($postFilters),
            'filters'       => $postFilters,
            'counts'        => $this->service->statusCounts(),
            'reportedPosts' => $tab === 'reported' ? $this->service->reportedPostList() : [],
            'pendingPosts'  => $tab === 'pending'  ? $this->service->pendingPostList()  : [],
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

    public function unreport(Post $post, String $status): RedirectResponse
    {
        try {
            $this->service->unreportPost($post, $status);
        } catch (PostModerationException $exception) {
            return back()->with('flash_error', $exception->getMessage());
        }

        return back()->with('flash_success', 'Post has been unreported');
    }

    public function markReportedSafe(Post $post): RedirectResponse
    {
        $this->service->markReportedSafe($post);

        return redirect()
            ->route('posts.index', ['status' => 'safe'])
            ->with('flash_success', 'Post marked as safe and all reports cleared.');
    }

    public function markReportedFlagged(Post $post): RedirectResponse
    {
        $this->service->markReportedFlagged($post);

        return redirect()
            ->route('posts.index', ['status' => 'flagged'])
            ->with('flash_success', 'Post flagged and all reports cleared.');
    }

    public function approvePendingAsSafe(PendingPost $pendingPost): RedirectResponse
    {
        $this->service->approvePendingAsSafe($pendingPost);

        return redirect()
            ->route('posts.index', ['status' => 'safe'])
            ->with('flash_success', 'Post published and marked as safe.');
    }

    public function approvePendingAsFlagged(PendingPost $pendingPost): RedirectResponse
    {
        $this->service->approvePendingAsFlagged($pendingPost);

        return redirect()
            ->route('posts.index', ['status' => 'flagged'])
            ->with('flash_success', 'Post not published and marked as flagged.');
    }
}