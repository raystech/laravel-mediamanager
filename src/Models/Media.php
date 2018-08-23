<?php

namespace Raystech\MediaManager\Models;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Responsable;
// use Raystech\MediaManager\Utils\File;
use Illuminate\Database\Eloquent\Model;
use Raystech\MediaManager\Models\Traits\CustomMediaProperties;
use Raystech\MediaManager\UrlGenerator\UrlGeneratorFactory;

class Media extends Model implements Responsable, Htmlable
{
  use CustomMediaProperties;

  protected $casts = [
    'custom_properties' => 'array', // Will convarted to (Array)
	];
  public function toResponse($request)
  {
    $downloadHeaders = [
      'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
      'Content-Type'        => $this->mime_type,
      'Content-Length'      => $this->size,
      'Content-Disposition' => 'attachment; filename="' . $this->file_name . '"',
      'Pragma'              => 'public',
    ];

    return response()->stream(function () {
      $stream = $this->stream();

      fpassthru($stream);

      if (is_resource($stream)) {
        fclose($stream);
      }
    }, 200, $downloadHeaders);
  }

  public function toHtml()
  {
    return $this->img();
  }
  public function img($conversion = '', array $extraAttributes = []): string
  {
    // if (! (new Image())->canHandleMime($this->mime_type)) {
    //     return '';
    // }

    // if (is_array($conversion)) {
    //     $attributes = $conversion;

    //     $conversion = $attributes['conversion'] ?? '';

    //     unset($attributes['conversion']);

    //     $extraAttributes = array_merge($attributes, $extraAttributes);
    // }

    // $attributeString = collect($extraAttributes)
    //     ->map(function ($value, $name) {
    //         return $name.'="'.$value.'"';
    //     })->implode(' ');

    // if (strlen($attributeString)) {
    //     $attributeString = ' '.$attributeString;
    // }

    // $media = $this;

    // $viewName = 'image';

    // $width = '';

    // if ($this->hasResponsiveImages($conversion)) {
    //     $viewName = config('medialibrary.responsive_images.use_tiny_placeholders')
    //         ? 'responsiveImageWithPlaceholder'
    //         : 'responsiveImage';

    //     $width = $this->responsiveImages($conversion)->files->first()->width();
    // }

    // return view("medialibrary::{$viewName}", compact(
    //     'media',
    //     'conversion',
    //     'attributeString',
    //     'width'
    // ));
  }

  public function getCustomProperty(string $propertyName, $default = null)
  {
    return array_get($this->custom_properties, $propertyName, $default);
  }

  /**
   * @param string $name
   * @param mixed $value
   *
   * @return $this
   */
  public function setCustomProperty(string $name, $value): self
  {
    $customProperties = $this->custom_properties;

    array_set($customProperties, $name, $value);

    $this->custom_properties = $customProperties;

    return $this;
  }
  public function getDiskDriverName(): string
    {
        return strtolower(config("filesystems.disks.{$this->disk}.driver"));
    }

    public function getFullUrl(string $conversionName = ''): string
    {
        return url($this->getUrl($conversionName));
    }

    /*
     * Get the url to a original media file.
     */
    public function getUrl(string $conversionName = ''): string
    {
        $urlGenerator = UrlGeneratorFactory::createForMedia($this, $conversionName);
        return $urlGenerator->getUrl();
    }
}
