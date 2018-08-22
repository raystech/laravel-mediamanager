<?php

namespace Raystech\MediaManager;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\File;
use Raystech\MediaManager\Conversion\Conversion;
use Raystech\MediaManager\Conversion\ConversionCollection;
use Raystech\MediaManager\Events\ConversionHasBeenCompleted;
use Raystech\MediaManager\Events\ConversionWillStart;
use Raystech\MediaManager\Filesystem;
use Raystech\MediaManager\Helpers\File as MediaLibraryFileHelper;
use Raystech\MediaManager\Helpers\ImageFactory;
use Raystech\MediaManager\Helpers\TemporaryDirectory;
use Raystech\MediaManager\ImageGenerators\ImageGenerator;
use Raystech\MediaManager\Jobs\PerformConversions;
use Raystech\MediaManager\Models\Media;
use Raystech\MediaManager\ResponsiveImages\ResponsiveImageGenerator;
use Storage;

class FileManipulator
{
  /**
   * Create all derived files for the given media.
   *
   * @param \Raystech\MediaManager\Models\RaystechMedia $media
   * @param array $only
   * @param bool $onlyIfMissing
   */
  public function createDerivedFiles(Media $media, array $only = [], $onlyIfMissing = false)
  {
    $profileCollection = ConversionCollection::createForMedia($media);

    if (!empty($only)) {
      $profileCollection = $profileCollection->filter(function ($collection) use ($only) {
        return in_array($collection->getName(), $only);
      });
    }

    // dd($profileCollection->getNonQueuedConversions($media->collection_name));

    $this->performConversions(
      $profileCollection->getNonQueuedConversions($media->collection_name),
      $media,
      $onlyIfMissing
    );

    $queuedConversions = $profileCollection->getQueuedConversions($media->collection_name);

    if ($queuedConversions->isNotEmpty()) {
      $this->dispatchQueuedConversions($media, $queuedConversions);
    }
  }

  /**
   * Perform the given conversions for the given media.
   *
   * @param \Raystech\MediaManager\Conversion\ConversionCollection $conversions
   * @param \Raystech\MediaManager\Models\RaystechMedia $media
   * @param bool $onlyIfMissing
   */
  public function performConversions(ConversionCollection $conversions, Media $media, $onlyIfMissing = false)
  {
    if ($conversions->isEmpty()) {
      return;
    }

    $imageGenerator = $this->determineImageGenerator($media);

    if (!$imageGenerator) {
      return;
    }

    $temporaryDirectory = TemporaryDirectory::create();

    $copiedOriginalFile = app(Filesystem::class)->copyFromMediaLibrary(
      $media,
      $temporaryDirectory->path(str_random(16) . '.' . $media->extension)
    );

    $conversions
      ->reject(function (Conversion $conversion) use ($onlyIfMissing, $media) {
        $relativePath = $media->getPath($conversion->getName());

        $rootPath = config('filesystems.disks.' . $media->disk . '.root');

        if ($rootPath) {
          $relativePath = str_replace($rootPath, '', $relativePath);
        }

        return $onlyIfMissing && Storage::disk($media->disk)->exists($relativePath);
      })
      ->each(function (Conversion $conversion) use ($media, $imageGenerator, $copiedOriginalFile) {
        event(new ConversionWillStart($media, $conversion, $copiedOriginalFile));

        $copiedOriginalFile = $imageGenerator->convert($copiedOriginalFile, $conversion);

        $conversionResult = $this->performConversion($media, $conversion, $copiedOriginalFile);

        $newFileName = pathinfo($media->file_name, PATHINFO_FILENAME) .
        '-' . $conversion->getName() .
        '.' . $conversion->getResultExtension(pathinfo($copiedOriginalFile, PATHINFO_EXTENSION));

        $renamedFile = MediaLibraryFileHelper::renameInDirectory($conversionResult, $newFileName);

        if ($conversion->shouldGenerateResponsiveImages()) {
          app(ResponsiveImageGenerator::class)->generateResponsiveImagesForConversion(
            $media,
            $conversion,
            $renamedFile
          );
        }

        app(Filesystem::class)->copyToMediaLibrary($renamedFile, $media, 'conversions');

        $media->markAsConversionGenerated($conversion->getName(), true);

        event(new ConversionHasBeenCompleted($media, $conversion));
      });

    $temporaryDirectory->delete();
  }

  public function performConversion(Media $media, Conversion $conversion, string $imageFile): string
  {
    $conversionTempFile = pathinfo($imageFile, PATHINFO_DIRNAME) . '/' . str_random(16)
    . $conversion->getName()
    . '.'
    . $media->extension;

    File::copy($imageFile, $conversionTempFile);

    $supportedFormats = ['jpg', 'pjpg', 'png', 'gif'];
    if ($conversion->shouldKeepOriginalImageFormat() && in_array($media->extension, $supportedFormats)) {
      $conversion->format($media->extension);
    }

    ImageFactory::load($conversionTempFile)
      ->manipulate($conversion->getManipulations())
      ->save();

    return $conversionTempFile;
  }

  protected function dispatchQueuedConversions(Media $media, ConversionCollection $queuedConversions)
  {
    $performConversionsJobClass = config('medialibrary.jobs.perform_conversions', PerformConversions::class);

    $job = new $performConversionsJobClass($queuedConversions, $media);

    if ($customQueue = config('medialibrary.queue_name')) {
      $job->onQueue($customQueue);
    }

    app(Dispatcher::class)->dispatch($job);
  }

  /**
   * @param \Raystech\MediaManager\Models\RaystechMedia $media
   *
   * @return \Raystech\MediaManager\ImageGenerators\ImageGenerator|null
   */
  public function determineImageGenerator(Media $media)
  {
    return $media->getImageGenerators()
      ->map(function (string $imageGeneratorClassName) {
        return app($imageGeneratorClassName);
      })
      ->first(function (ImageGenerator $imageGenerator) use ($media) {
        return $imageGenerator->canConvert($media);
      });
  }
}
