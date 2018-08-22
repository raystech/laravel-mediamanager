<?php

namespace Raystech\MediaManager\Media;

use Raystech\MediaManager\MediaAdder\MediaAdderFactory;
use Raystech\MediaManager\MediaCollection\MediaCollection;
use Raystech\MediaManager\Models\Media;

trait MediaTrait
{
  /** @var array */
  public $mediaConversions = [];
  /** @var array */
  public $mediaCollections = [];
  /** @var bool */
  protected $deletePreservingMedia = false;
  /** @var array */
  protected $unAttachedMediaItems = [];
  public static function bootMediaTrait()
  {
    return 'booting';
  }
  protected $path       = '';
  protected $collection = 'image';

  // public function addImage($file, $options = [])
  //  {
  //    $width  = array_key_exists('width', $options) ? $options['width'] : 860;
  //    $height = array_key_exists('height', $options) ? $options['height'] : 860;
  //   return $width;
  //  }

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

  public function addMedia($file)
  {
    echo 'adding media';
    return app(MediaAdderFactory::class)->create($this, $file);
  }

  public function addImage($image)
  {
    echo 'adding media';
    return app(MediaAdderFactory::class)->create($this, $file);
  }

  public function prepareToAttachMedia(Media $media, FileAdder $fileAdder)
  {
    $this->unAttachedMediaItems[] = compact('media', 'fileAdder');
  }

  public function processUnattachedMedia(callable $callable)
  {
    foreach ($this->unAttachedMediaItems as $item) {
      $callable($item['media'], $item['fileAdder']);
    }

    $this->unAttachedMediaItems = [];
  }

  public function addMediaCollection(string $name): MediaCollection
  {
    $mediaCollection = MediaCollection::create($name);

    $this->mediaCollections[] = $mediaCollection;

    return $mediaCollection;
  }

  public function media()
  {
    return $this->morphMany(config('mediamanager.media_model'), 'model');
  }

  public function registerMediaConversions(Media $media = null)
    {
    }

  public function registerAllMediaConversions(Media $media = null)
    {
        $this->registerMediaCollections();

        collect($this->mediaCollections)->each(function (MediaCollection $mediaCollection) use ($media) {
            $actualMediaConversions = $this->mediaConversions;

            $this->mediaConversions = [];

            ($mediaCollection->mediaConversionRegistrations)($media);

            $preparedMediaConversions = collect($this->mediaConversions)
                ->each(function (Conversion $conversion) use ($mediaCollection) {
                    $conversion->performOnCollections($mediaCollection->name);
                })
                ->values()
                ->toArray();

            $this->mediaConversions = array_merge($actualMediaConversions, $preparedMediaConversions);
        });

        $this->registerMediaConversions($media);
    }

  public function registerMediaCollections()
  {
  }

}
