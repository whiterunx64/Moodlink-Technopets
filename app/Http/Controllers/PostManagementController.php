<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;


class PostManagementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('PostManagement/Index', [
            'posts' => [
                [
                    'id' => 1,
                    'title' => 'First Post',
                    'author' => 'John Doe',
                    'content' => 'This is a sample post content for testing UI layout.',
                    'section' => 'General',
                    'date' => '2026-06-01',
                    'time' => '10:30 AM',
                    'mood' => 'happy',
                    'flagged' => false,
                ],
                [
                    'id' => 2,
                    'title' => 'Second Post',
                    'author' => 'Jane Smith',
                    'content' => 'Another example post used to test flagged state.',
                    'section' => 'Updates',
                    'date' => '2026-06-01',
                    'time' => '11:15 AM',
                    'mood' => 'anxious',
                    'flagged' => true,
                ],
                [
                    'id' => 3,
                    'title' => 'Third Post',
                    'author' => 'Alex Cruz',
                    'content' => 'More sample content to verify filtering and layout.',
                    'section' => 'News',
                    'date' => '2026-06-02',
                    'time' => '09:00 AM',
                    'mood' => 'neutral',
                    'flagged' => false,
                ],
                [
                    'id' => 4,
                    'title' => 'Fourth Post',
                    'author' => 'Maria Lopez',
                    'content' => 'Testing UI responsiveness and grid layout.',
                    'section' => 'General',
                    'date' => '2026-06-02',
                    'time' => '01:45 PM',
                    'mood' => 'sad',
                    'flagged' => false,
                ],
                [
                    'id' => 5,
                    'title' => 'Fifth Post',
                    'author' => 'Chris Evans',
                    'content' => 'Flagged post example for moderation testing.',
                    'section' => 'Reports',
                    'date' => '2026-06-02',
                    'time' => '03:10 PM',
                    'mood' => 'happy',
                    'flagged' => true,
                ],
            ],
        ]);
    }
}
