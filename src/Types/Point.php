<?php

namespace Lyhty\Geometry\Types;

use Exception;
use Lyhty\Geometry\Contracts\SingleGeometryElement;

class Point extends Geometry implements SingleGeometryElement
{
    protected array $coords = [0, 0];

    protected static string $geomType = 'Point';

    protected int $dimension = 2;

    /**
     * The Point constructor
     *
     * @param  float|null  $x The x coordinate (or longitude)
     * @param  float|null  $y The y coordinate (or latitude)
     * @param  float|null  $z The z coordinate (or altitude) [optional]
     */
    public function __construct(float $x = null, float $y = null, float $z = null)
    {
        // Check if it's an empty point
        if ($x === null && $y === null) {
            $this->coords = [null, null];
            $this->dimension = 0;

            return;
        }

        // Basic validation on x and y
        if (! is_float($x) || ! is_float($y)) {
            throw new Exception('Cannot construct Point. x and y should be numeric');
        }

        // Check to see if this is a 3D point
        if ($z !== null) {
            if (! is_float($z)) {
                throw new Exception('Cannot construct Point. z should be numeric');
            }

            $this->dimension = 3;
        }

        // Convert to floatval in case they are passed in as a string or integer etc.
        $x = floatval($x);
        $y = floatval($y);
        $z = floatval($z);

        // Add poitional elements
        if ($this->dimension == 2) {
            $this->coords = [$x, $y];
        }
        if ($this->dimension == 3) {
            $this->coords = [$x, $y, $z];
        }
    }

    /**
     * Get X (longitude) coordinate
     *
     * @return float The X coordinate
     */
    public function getX(): float
    {
        return $this->coords[0];
    }

    /**
     * Alias for `static::getX()`.
     *
     * @return float
     */
    public function getLng(): float
    {
        return $this->getX();
    }

    /**
     * Returns Y (latitude) coordinate
     *
     * @return float The Y coordinate
     */
    public function getY(): float
    {
        return $this->coords[1];
    }

    /**
     * Alias for `static::getY()`.
     *
     * @return float
     */
    public function getLat(): float
    {
        return $this->getY();
    }

    /**
     * Returns Z (altitude) coordinate
     *
     * @return float The Z coordinate or null is not a 3D point
     */
    public function z(): ?float
    {
        return $this->dimension == 3
            ? $this->coords[2]
            : null;
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
        return $this->coords;
    }

    /**
     * {@inheritDoc}
     */
    public function toGeoJsonArray(): array
    {
        return $this->getCoordinates();
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty(): bool
    {
        return $this->dimension === 0;
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
        } elseif ($this->isEmpty() && $geometry->isEmpty()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isSimple(): bool
    {
        return true;
    }
}
