<?php

namespace Raystech\MediaManager\UrlGenerator;

use DateTimeInterface;
use Raystech\MediaManager\Models\Media;
use Raystech\MediaManager\Conversion\Conversion;
use Raystech\MediaManager\PathGenerator\PathGenerator;

interface UrlGenerator
{
    /**
     * Get the url for a media item.
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * @param \Raystech\MediaManager\Models\Media $media
     *
     * @return \Raystech\MediaManager\UrlGenerator\UrlGenerator
     */
    public function setMedia(Media $media): self;

    /**
     * @param \Raystech\MediaManager\Conversion\Conversion $conversion
     *
     * @return \Raystech\MediaManager\UrlGenerator\UrlGenerator
     */
    public function setConversion(Conversion $conversion): self;

    /**
     * Set the path generator class.
     *
     * @param \Raystech\MediaManager\PathGenerator\PathGenerator $pathGenerator
     *
     * @return \Raystech\MediaManager\UrlGenerator\UrlGenerator
     */
    public function setPathGenerator(PathGenerator $pathGenerator): self;

    /**
     * Get the temporary url for a media item.
     *
     * @param DateTimeInterface $expiration
     * @param array              $options
     *
     * @return string
     */
    public function getTemporaryUrl(DateTimeInterface $expiration, array $options = []): string;

    /**
     * Get the url to the directory containing responsive images.
     *
     * @return string
     */
    public function getResponsiveImagesDirectoryUrl(): string;
}
