<?php

namespace Raystech\MediaManager\UrlGenerator;

use Raystech\MediaManager\Models\Media;
use Raystech\MediaManager\Conversion\Conversion;
use Raystech\MediaManager\PathGenerator\PathGenerator;
use Illuminate\Contracts\Config\Repository as Config;

abstract class BaseUrlGenerator implements UrlGenerator
{
    /** @var \Raystech\MediaManager\Models\Media */
    protected $media;

    /** @var \Raystech\MediaManager\Conversion\Conversion */
    protected $conversion;

    /** @var \Raystech\MediaManager\PathGenerator\PathGenerator */
    protected $pathGenerator;

    /** @var \Illuminate\Contracts\Config\Repository */
    protected $config;

    /** @param \Illuminate\Contracts\Config\Repository $config */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param \Raystech\MediaManager\Models\Media $media
     *
     * @return \Raystech\MediaManager\UrlGenerator\UrlGenerator
     */
    public function setMedia(Media $media): UrlGenerator
    {
        $this->media = $media;

        return $this;
    }

    /**
     * @param \Raystech\MediaManager\Conversion\Conversion $conversion
     *
     * @return \Raystech\MediaManager\UrlGenerator\UrlGenerator
     */
    public function setConversion(Conversion $conversion): UrlGenerator
    {
        $this->conversion = $conversion;

        return $this;
    }

    /**
     * @param \Raystech\MediaManager\PathGenerator\PathGenerator $pathGenerator
     *
     * @return \Raystech\MediaManager\UrlGenerator\UrlGenerator
     */
    public function setPathGenerator(PathGenerator $pathGenerator): UrlGenerator
    {
        $this->pathGenerator = $pathGenerator;

        return $this;
    }

    /*
     * Get the path to the requested file relative to the root of the media directory.
     */
    public function getPathRelativeToRoot(): string
    {
        if (is_null($this->conversion)) {
            return $this->pathGenerator->getPath($this->media).($this->media->file_name);
        }

        return $this->pathGenerator->getPathForConversions($this->media)
            .pathinfo($this->media->file_name, PATHINFO_FILENAME)
            .'-'.$this->conversion->getName()
            .'.'
            .$this->conversion->getResultExtension($this->media->extension);
    }

    public function rawUrlEncodeFilename(string $path = ''): string
    {
        return pathinfo($path, PATHINFO_DIRNAME).'/'.rawurlencode(pathinfo($path, PATHINFO_BASENAME));
    }
}
