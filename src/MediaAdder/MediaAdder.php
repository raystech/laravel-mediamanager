<?php
namespace Raystech\MediaManager\MediaAdder;

use Illuminate\Database\Eloquent\Model;
use Raystech\MediaManager\File as PendingFile;
use Raystech\MediaManager\Filesystem;
use Raystech\MediaManager\MediaCollection\MediaCollection;
use Raystech\MediaManager\Models\Media;
use Raystech\MediaManager\Utils\File;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 *
 */
class MediaAdder
{
  /** @var \Illuminate\Database\Eloquent\Model subject */
  protected $subject;

  /** @var \Raystech\MediaManager\Filesystem\Filesystem */
  protected $filesystem;

  /** @var bool */
  protected $preserveOriginal = false;

  /** @var string|\Symfony\Component\HttpFoundation\File\UploadedFile */
  protected $file;

  /** @var array */
  protected $properties = [];

  /** @var array */
  protected $customProperties = [];
  protected $customPropertiesStr;

  /** @var array */
  protected $manipulations = [];

  /** @var string */
  protected $pathToFile;

  /** @var string */
  protected $fileName;

  /** @var string */
  protected $mediaName;

  /** @var string */
  protected $diskName = '';

  /** @var null|callable */
  protected $fileNameSanitizer;

  /** @var bool */
  protected $generateResponsiveImages = false;

  /** @var array */
  protected $customHeaders = [];

  public function __construct(Filesystem $fileSystem)
  {
    $this->filesystem = $fileSystem;

    $this->fileNameSanitizer = function ($fileName) {
      return $this->defaultSanitizer($fileName);
    };
  }

  public function setSubject(Model $subject)
  {
    $this->subject = $subject;

    return $this;
  }

  public function setFile($file): self
  {
    $this->file = $file;
    // dd($file);

    if (is_string($file)) {
      $this->pathToFile = $file;
      $this->setFileName(pathinfo($file, PATHINFO_BASENAME));
      $this->mediaName = pathinfo($file, PATHINFO_FILENAME);

      return $this;
    }

    if ($file instanceof UploadedFile) {
      $this->pathToFile = $file->getPath() . '/' . $file->getFilename();
      $this->setFileName($file->getClientOriginalName());
      $this->mediaName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

      return $this;
    }

    if ($file instanceof SymfonyFile) {
      $this->pathToFile = $file->getPath() . '/' . $file->getFilename();
      $this->setFileName(pathinfo($file->getFilename(), PATHINFO_BASENAME));
      $this->mediaName = pathinfo($file->getFilename(), PATHINFO_FILENAME);

      return $this;
    }

    throw UnknownType::create();
  }

  public function setFileName(string $fileName): self
  {
    $this->fileName = $fileName;

    return $this;
  }

  public function toCollection(string $collectionName = 'default', string $diskName = ''): Media
  {
    if (!is_file($this->pathToFile)) {
      // throw FileDoesNotExist::create($this->pathToFile);
      throw new Exception("File {$this->pathToFile} does not exists", 1);

    }

    if (filesize($this->pathToFile) > config('mediamanager.max_file_size')) {
      // throw FileIsTooBig::create($this->pathToFile);
      throw new Exception("File is too big!", 1);

    }

    $mediaClass = config('mediamanager.media_model');
    $media      = new $mediaClass();

    $media->name = $this->mediaName;

    // $this->fileName = ($this->fileNameSanitizer)($this->fileName);
    $this->fileName = $this->fileName;

    $media->file_name = $this->fileName;

    $media->disk = $this->determineDiskName($diskName, $collectionName);

    if (is_null(config("filesystems.disks.{$media->disk}"))) {
      // throw DiskDoesNotExist::create($media->disk);
      throw new Exception("Disk doesn't exists!", 1);

    }

    $media->collection_name = $collectionName;

    $media->mime_type = File::getMimetype($this->pathToFile);
    $media->size      = filesize($this->pathToFile);
    // $media->custom_properties = $this->customProperties;
    $media->custom_properties = '[]';

    // $media->responsive_images = [];
    $media->responsive_images = '[]';

    // $media->manipulations = $this->manipulations;
    $media->manipulations = '[]';

    // $media->setCustomHeaders($this->customHeaders);

    $media->fill($this->properties);

    $this->attachMedia($media);

    return $media;
  }

  protected function determineDiskName(string $diskName, string $collectionName): string
  {
    if ($diskName !== '') {
      return $diskName;
    }

    if ($collection = $this->getMediaCollection($collectionName)) {
      $collectionDiskName = $collection->diskName;

      if ($collectionDiskName !== '') {
        return $collectionDiskName;
      }
    }

    return config('mediamanager.disk_name');
  }

  public function defaultSanitizer(string $fileName): string
  {
    return str_replace(['#', '/', '\\', ' '], '-', $fileName);
  }

  protected function getMediaCollection(string $collectionName):  ? MediaCollection
  {
    $this->subject->registerMediaCollections();

    return collect($this->subject->mediaCollections)
      ->first(function (MediaCollection $collection) use ($collectionName) {
        return $collection->name === $collectionName;
      });
  }

  protected function attachMedia(Media $media)
  {
    // dd(class_basename($this->subject));
    if (!$this->subject->exists) {
      $this->subject->prepareToAttachMedia($media, $this);

      $class = get_class($this->subject);

      $class::created(function ($model) {
        $model->processUnattachedMedia(function (Media $media, MediaAdder $mediaAdder) use ($model) {
          $this->processMediaItem($model, $media, $mediaAdder);
        });
      });

      return;
    }

    $this->processMediaItem($this->subject, $media, $this);
  }

  protected function processMediaItem($model, Media $media, self $mediaAdder)
  {
    $this->guardAgainstDisallowedFileAdditions($media, $model);

    $model->media()->save($media);

    $this->filesystem->add($mediaAdder->pathToFile, $media, $mediaAdder->fileName);

    if (!$mediaAdder->preserveOriginal) {
      unlink($mediaAdder->pathToFile);
    }

    // if ($this->generateResponsiveImages && (new ImageGenerator())->canConvert($media)) {
    //   $generateResponsiveImagesJobClass = config('medialibrary.jobs.generate_responsive_images', GenerateResponsiveImages::class);

    //   $job = new $generateResponsiveImagesJobClass($media);

    //   if ($customQueue = config('medialibrary.queue_name')) {
    //     $job->onQueue($customQueue);
    //   }

    //   dispatch($job);
    // }

    if (optional($this->getMediaCollection($media->collection_name))->singleFile) {
      $model->clearMediaCollectionExcept($media->collection_name, $media);
    }
  }

  protected function guardAgainstDisallowedFileAdditions(Media $media)
  {
    $file = PendingFile::createFromMedia($media);

    if (!$collection = $this->getMediaCollection($media->collection_name)) {
      return;
    }

    if (!($collection->acceptsFile)($file, $this->subject)) {
      throw FileUnacceptableForCollection::create($file, $collection, $this->subject);
    }
  }
}
