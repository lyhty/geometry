<?php

namespace Lyhty\Geometry\Types;

use Lyhty\Geometry\Contracts\SingleGeometryElement;

class Point extends Geometry implements SingleGeometryElement
{
    protected static string $geomType = 'Point';

    /**
     * The Point constructor
     *
     * @param  float  $x The x coordinate (or longitude)
     * @param  float  $y The y coordinate (or latitude)
     * @param  float  $z The z coordinate (or altitude) [optional]
     */
    public function __construct(
        protected ?float $x = null,
        protected ?float $y = null,
        protected ?float $z = null
    ) {
    }

    /**
     * Get X (longitude) coordinate
     *
     * @return float|null
     */
    public function getX(): ?float
    {
        return $this->x;
    }

    /**
     * Alias for `static::getX()`.
     *
     * @return float|null
     */
    public function getLng(): ?float
    {
        return $this->getX();
    }

    /**
     * Returns Y (latitude) coordinate
     *
     * @return float|null
     */
    public function getY(): ?float
    {
        return $this->y;
    }

    /**
     * Alias for `static::getY()`.
     *
     * @return float|null
     */
    public function getLat(): ?float
    {
        return $this->getY();
    }

    /**
     * Returns Z (altitude) coordinate
     *
     * @return float|null
     */
    public function getZ(): ?float
    {
        return $this->z;
    }

    /**
     * Alias for `static::getZ()`.
     *
     * @return float|null
     */
    public function getAlt(): ?float
    {
        return $this->getZ();
    }

    /**
     * {@inheritDoc}
     */
    public function getBBox(): ?array
    {
        return [
            'maxy' => $this->getY(),
            'miny' => $this->getY(),
            'maxx' => $this->getX(),
            'minx' => $this->getX(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function explode(): ?array
    {
        return null;
    }

    /**
     * Return the coordinates of the Point.
     *
     * @return array
     */
    public function getCoordinates(): array
    {
        $coords = ['x' => $this->getX(), 'y' => $this->getY()];

        if (!is_null($z = $this->getZ())) {
            $coords['z'] = $z;
        }

        return $coords;
    }

    /**
     * {@inheritDoc}
     */
    public function toGeoJsonArray(): array
    {
        return array_values($this->getCoordinates());
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function numPoints(): int
    {
        return 1;
    }

    /**
     * {@inheritDoc}
     */
    public function centroid(): ?self
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getPointsFlatMap(): array
    {
        return [$this];
    }

    /**
     * {@inheritDoc}
     */
    public function equals(Geometry $geometry): bool
    {
        if (! $geometry instanceof self) {
            return false;
        }

        if (! $this->isEmpty() && ! $geometry->isEmpty()) {
            return $this->getX() == $geometry->getX() && $this->getY() == $geometry->getY();
        }

        if ($this->isEmpty() && $geometry->isEmpty()) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isSimple(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function dimension(): int
    {
        return 0;
    }
}
