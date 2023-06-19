<?php

namespace Lyhty\Geometry\Exceptions;

use RuntimeException;

class InvalidGeoJsonException extends RuntimeException
{
    public static function invalidJson()
    {
        return new static('Invalid JSON object');
    }

    public static function unsupportedType($type)
    {
        return new static("Invalid GeoJSON: Unsupported type {$type} given.");
    }

    public static function emptyGeometryCollection()
    {
        return new static('Invalid GeoJSON: GeometryCollection with no component geometries.');
    }
}
