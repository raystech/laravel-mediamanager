<?php

namespace raystech\mediamanager\Media;

use Carbon\Carbon;

trait MediaTrait
{
  /** @var array */
  public $mediaConversions = [];
  /** @var array */
  public $mediaCollections = [];
  /** @var bool */
  protected $deletePreservingMedia = false;
  /** @var array */
  protected $unAttachedMediaManagerItems = [];
  public static function bootMediaTrait()
  {
  	return 'booting';
  }
  protected $path = '';
	protected $collection = 'image';

	public function addImage($file, $options = [])
  {
  	$width  = array_key_exists('width', $options) ? $options['width'] : 860;
  	$height = array_key_exists('height', $options) ? $options['height'] : 860;
		return $width;
  }

  public function toCollection(string $collection = null)
  {
  	switch ($collection) {
  		case 'car':
  			$this->path = sprintf("%07d/", $this->id);
  			break;
  		
  		default:
  			# code...
  			break;
  	}
  	$this->collection = $collection;
  	return $this->path;
  }

}