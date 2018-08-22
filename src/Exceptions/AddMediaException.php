<?php

namespace Raystech\MediaManager\Exceptions;

use Raystech\MediaManager\Utils\File;
use Exception;

class AddMediaException extends Exception
{
  public static function fileIsTooBig(string $path)
  {
    $fileSize = File::getHumanReadableSize(filesize($path));

    $maxFileSize = File::getHumanReadableSize(config('medialibrary.max_file_size'));

    return new static("File `{$path}` has a size of {$fileSize} which is greater than the maximum allowed {$maxFileSize}");
  }

  public static function fileDoesNotExist(string $path)
  {
    return new static("File `{$path}` does not exist");
  }

  public static function unknownType()
  {
    return new static("Only strings, FileObjects and UploadedFileObjects can be imported");
  }

  public static function diskDoesNotExist($diskName)
    {
        return new static("There is no filesystem disk named `{$diskName}`");
    }
}
