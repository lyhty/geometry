<?php

namespace Lyhty\Geometry\Types;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Lyhty\Geometry\Concerns\Formatting;
use Lyhty\Geometry\Concerns\InteractsWithGeos;
use Lyhty\Geometry\Concerns\Operations;
use Lyhty\Geometry\Concerns\Predicates;
use Lyhty\Geometry\Exceptions\GeosNotInstalledException;

abstract class Geometry implements Jsonable, Arrayable, JsonSerializable
{
    use InteractsWithGeos,
        Predicates,
        Operations,
        Formatting;

    protected ?int $srid = null;

    protected static string $geomType;

    /**
     * Returns the name of the instantiable subtype of Geometry of which this
     * geometric object is an instantiable member. The name of the subtype of
     * Geometry is returned as a string.
     *
     * @return string
     */
    public static function geometryType(): string
    {
        return static::$geomType;
    }

    /**
     * Get the given geometry as an array of geojson components (recursive).
     *
     * @return array
     */
    public function getGeoJsonDataKey(): string
    {
        return 'coordinates';
    }

    /**
     * The mathematical centroid for this geometry as a Point. For polygons, the
     * result is not guaranteed to be interior.
     *
     * @return \Lyhty\Geometry\Types\Point|null
     */
    abstract public function centroid(): ?Point;

    /**
     * A Point guaranteed to be within a polygon.
     *
     * @uses geos
     *
     * @return \Lyhty\Geometry\Types\Point
     */
    public function pointOnSurface(): Point
    {
        return $this->forwardCallToGeos(__FUNCTION__);
    }

    /**
     * The number of points in the geometry.
     *
     * @return int
     */
    abstract public function numPoints(): int;

    /**
     * Return boolean value whether the geometry is empty.
     *
     * @return bool
     */
    abstract public function isEmpty(): bool;

    /**
     * Return boolean value whether the geometry does not pass through the
     * same point in space more than once.
     *
     * @return bool
     */
    abstract public function isSimple(): bool;

    /**
     * The minimum bounding box for this Geometry, returned as an array.
     *
     * @return float[]|null
     */
    abstract public function getBBox(): ?array;

    /**
     * Get an array of points.
     *
     * @return \Lyhty\Geometry\Types\Point[]
     */
    abstract public function getPointsFlatMap(): array;

    /**
     * Explode the geometry into an array of LineString instances.
     *
     * @return array|null
     */
    abstract public function explode(): ?array;

    /**
     * Get the dimensions of the geometry.
     *
     * @return int
     */
    public function dimension(): int
    {
        return 0;
    }

    /**
     * Returns the Spatial Reference System ID for this geometric object.
     *
     * @return int
     */
    public function SRID(): int
    {
        return $this->srid ?: 0;
    }

    /**
     * Set the Spatial Reference System ID for this geometric object.
     *
     * @uses geos
     *
     * @param  int  $srid
     * @return void
     */
    public function setSRID(int $srid): void
    {
        try {
            $this->geos()->setSRID($srid);
        } catch (GeosNotInstalledException $th) {
        }

        $this->srid = $srid;
    }

    public function hasZ(): bool
    {
        // geoPHP does not support Z values at the moment
        return false;
    }

    public function is3D(): bool
    {
        // geoPHP does not support 3D geometries at the moment
        return false;
    }

    public function isMeasured(): bool
    {
        // geoPHP does not yet support M values
        return false;
    }

    public function coordinateDimension(): int
    {
        // geoPHP only supports 2-dimensional space
        return 2;
    }
}
