<?php

namespace App\Actions;

use App\Models\BuildPost;
use App\Enums\PostStatus;

class TogglePostStatus
{
  public function execute(BuildPost $post): void
  {
    if ($post->status === PostStatus::Flagged->value) {
      $post->update(['status' => PostStatus::Safe->value]);
      return;
    }

    $post->update(['status' => PostStatus::Flagged->value]);
  }
}