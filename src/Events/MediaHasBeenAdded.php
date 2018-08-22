<?php

namespace Raystech\MediaManager\Events;

use Raystech\MediaManager\Models\Media;
use Illuminate\Queue\SerializesModels;

class MediaHasBeenAdded
{
    use SerializesModels;

    /** @var \Raystech\MediaManager\Models\Media */
    public $media;

    public function __construct(Media $media)
    {
        $this->media = $media;
    }
}
