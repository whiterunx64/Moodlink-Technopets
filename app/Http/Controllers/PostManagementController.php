<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostManagementFilterRequest;
use App\Actions\TogglePostStatus;
use App\Mappers\BuildPostMapper;
use App\Models\BuildPost;
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

    public function index(): Response
    {
        return Inertia::render('PostManagement/Index', [
            'posts' => $this->mapper->toDTOCollection(
                $this->query->listLatest()
            ),
        ]);
    }

    public function filterBySection(PostManagementFilterRequest $request, string $section): Response
    {
        return Inertia::render('PostManagement/Index', [
            'posts' => $this->mapper->toDTOCollection(
                $this->query->listBySection($section)
            ),
            'activeSection' => $section,
        ]);
    }

    public function filterByMood(PostManagementFilterRequest $request, string $mood): Response
    {
        return Inertia::render('PostManagement/Index', [
            'posts' => $this->mapper->toDTOCollection(
                $this->query->listByMood($mood)
            ),
            'activeMood' => $mood,
        ]);
    }
    public function toggleFlag(BuildPost $post): RedirectResponse
    {
        $this->toggleStatus->execute($post);

        return back();
    }

}
