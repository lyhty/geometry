<?php

namespace Lyhty\Geometry;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Lyhty\Geometry\Types\Geometry|null parse(mixed $data, string $type = null, mixed ...$otherArgs)
 * @method static mixed format(\Lyhty\Geometry\Types\Geometry $geometry, string $format, mixed ...$otherArgs)
 * @method static \Lyhty\Geometry\Types\Geometry|null wrap(\Lyhty\Geometry\Types\Geometry|\Lyhty\Geometry\Types\Geometry[] $geometries)
 * @method static \Lyhty\Geometry\Types\Geometry|null geosToGeometry(\GEOSGeometry $geos)
 * @method static \Lyhty\Geometry\Types\Geometry|null reduce(\Lyhty\Geometry\Types\Geometry|\Lyhty\Geometry\Types\Geometry[] $geometry)
 * @method static string|null detectFormat(mixed &$input)
 * @method static array geometryList()
 * @method static array lcGeometryList()
 * @method static \GEOSGeometry&GeosGeometryWrapper|null makeGeometry()
 * @method static \GEOSWKBReader|null makeWKBReader()
 * @method static \GEOSWKBWriter|null makeWKBWriter()
 * @method static \GEOSWKTReader|null makeWKTReader()
 * @method static \GEOSWKTWriter|null makeWKTWriter()
 * @method static bool geosInstalled()
 * @method static bool isGeos(mixed $value)
 * @method static \Lyhty\Geometry\Adapters\GeoAdapter|null makeAdapter(string $type)
 * @method static void extend(string $name, \Closure|string $extension, \Closure|null $write = null)
 * @method static \Lyhty\Geometry\Types\Geometry|null parseWkt($data)
 * @method static \Lyhty\Geometry\Types\Geometry|null parseEwkt($data)
 * @method static \Lyhty\Geometry\Types\Geometry|null parseWkb($data, bool $isHex = false)
 * @method static \Lyhty\Geometry\Types\Geometry|null parseEwkb($data, bool $isHex = false)
 * @method static \Lyhty\Geometry\Types\Geometry|null parseGeoJson($data)
 * @method static \Lyhty\Geometry\Types\Geometry|null parseKml($data)
 * @method static \Lyhty\Geometry\Types\Geometry|null parseGpx($data)
 * @method static \Lyhty\Geometry\Types\Geometry|null parseGeoRss($data)
 * @method static \Lyhty\Geometry\Types\Geometry|null parseGoogleGeocode($address, string $token = '')
 * @method static \Lyhty\Geometry\Types\Geometry|null parseGeoHash($data, bool $asGrid = false)
 * @method static string formatWkt($geom)
 * @method static string formatEwkt($geom)
 * @method static string formatWkb($geom, bool $asHex = false)
 * @method static string formatEwkb($geom, bool $asHex = false)
 * @method static string formatGeoJson($geom, bool $asArray = false)
 * @method static string formatKml($geom, string $namespace = null)
 * @method static string formatGpx($geom, string $namespace = null)
 * @method static string formatGeoRss($geom, string $namespace = null)
 * @method static string formatGoogleGeocode($geom, string $token = '')
 * @method static string formatGeoHash($geom, bool $precision = null)
 *
 * @see \Lyhty\Geometry\Factory
 */
class Geom extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'lyhty-geometry';
    }
}
