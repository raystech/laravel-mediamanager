<?php

namespace Raystech\MediaManager\PathGenerator;

use Raystech\MediaManager\Exceptions\InvalidPathGenerator;

class PathGeneratorFactory
{
    public static function create()
    {
        $pathGeneratorClass = BasePathGenerator::class;

        $customPathClass = config('mediamanager.path_generator');

        if ($customPathClass) {
            $pathGeneratorClass = $customPathClass;
        }

        static::guardAgainstInvalidPathGenerator($pathGeneratorClass);

        return app($pathGeneratorClass);
    }

    protected static function guardAgainstInvalidPathGenerator(string $pathGeneratorClass)
    {
        if (! class_exists($pathGeneratorClass)) {
            throw InvalidPathGenerator::doesntExist($pathGeneratorClass);
        }

        if (! is_subclass_of($pathGeneratorClass, PathGenerator::class)) {
            throw InvalidPathGenerator::isntAPathGenerator($pathGeneratorClass);
        }
    }
}
