<?php

namespace Raystech\MediaManager\MediaAdder;

use Illuminate\Database\Eloquent\Model;
/**
 * 
 */
class MediaAdderFactory
{
	
	public static function create(Model $subject, $file)
  {
      return app(MediaAdder::class)
          ->setSubject($subject)
          ->setFile($file);
  }
}