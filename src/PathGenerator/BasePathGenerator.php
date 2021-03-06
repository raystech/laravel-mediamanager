<?php

namespace Raystech\MediaManager\PathGenerator;

use Raystech\MediaManager\Models\Media;
use Carbon\Carbon;

class BasePathGenerator implements PathGenerator
{
  /*
   * Get the path for the given media, relative to the root storage path.
   */
  public function getPath(Media $media): string
  {
    return $this->getBasePath($media) . '/';
  }

  /*
   * Get the path for conversions of the given media, relative to the root storage path.
   */
  public function getPathForConversions(Media $media): string
  {
    return $this->getBasePath($media) . '/conversions/';
  }

  /*
   * Get the path for responsive images of the given media, relative to the root storage path.
   */
  public function getPathForResponsiveImages(Media $media): string
  {
    return $this->getBasePath($media) . '/responsive-images/';
  }

  /*
   * Get a unique base path for the given media.
   */
  protected function getBasePath(Media $media): string
  {
  	$date  = Carbon::parse($media->created_at);
  	$month = $date->format('m');
  	$year  = $date->format('Y');
  	$path   = "{$year}/{$month}";
  	return $path;
    // return $media->getKey();
  }
}
