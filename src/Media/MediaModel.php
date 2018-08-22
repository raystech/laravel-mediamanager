<?php

namespace Raystech\MediaManager\Media;

use Raystech\MediaManager\Models\Media;

interface MediaModel
{
	public function media();
	public function registerAllMediaConversions();
}