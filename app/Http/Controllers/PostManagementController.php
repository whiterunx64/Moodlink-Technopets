<?php

namespace App\Http\Controllers;

use App\Actions\TogglePostStatus;
use App\Http\Requests\PostManagementFilterRequest;
use App\Mappers\BuildPostMapper;
use App\Models\Post;
use App\Queries\BuildPostQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PostManagementController extends Controller
{
    public function __construct(
        private readonly BuildPostQuery $query,
        private readonly BuildPostMapper $mapper,
        private readonly TogglePostStatus $toggleStatus,
    ) {
    }

    public function index(PostManagementFilterRequest $request): Response
    {
        // Validation Restriction
        $validated = $request->validated();

        $posts = match (true) {
            isset($validated['status']) => $this->query->listByStatus($validated['status']),
            isset($validated['section']) => $this->query->listBySection($validated['section']),
            isset($validated['mood']) => $this->query->listByMood($validated['mood']),
            default => $this->query->listLatest(),
        };

        return Inertia::render('PostManagement/Index', [
            'posts' => $this->mapper->toDTOCollection($posts),
            'filters' => [
                'status' => $validated['status'] ?? null,
                'section' => $validated['section'] ?? null,
                'mood' => $validated['mood'] ?? null,
            ],
        ]);
    }

    /**
     * Toggles a post's status between Flagged and Safe, then redirects back.
     * @param Post $post
     * @return RedirectResponse
     */
    public function toggleFlag(Post $post): RedirectResponse
    {
        $this->toggleStatus->execute($post);

        return back();
    }
}
