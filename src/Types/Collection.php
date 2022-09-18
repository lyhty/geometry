<?php

namespace Lyhty\Geometry\Types;

use ArrayIterator;
use Countable;
use Illuminate\Support\Str;
use InvalidArgumentException;
use IteratorAggregate;
use Lyhty\Geometry\Exceptions\GeosNotInstalledException;
use Lyhty\Geometry\Geom;
use Traversable;

abstract class Collection extends Geometry implements Countable, IteratorAggregate
{
    /**
     * @var Geometry[]
     */
    protected array $components = [];

    protected static string $collectionComponentClass;

    protected static int $minimumComponentCount;

    /**
     * Constructor: Checks and sets component geometries.
     *
     * @param  array  $components  array of geometries
     */
    public function __construct(array $components = [])
    {
        $this->validateItems($components);

        $this->components = $components;
    }

    /**
     * Get the components of the geometry.
     *
     * @return \Lyhty\Geometry\Types\Geometry[]
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * {@inheritDoc}
     */
    public function centroid(): ?Point
    {
        if ($this->isEmpty()) {
            return null;
        }

        try {
            $centroid = $this->forwardCallToGeos(__FUNCTION__);

            if ($centroid->geometryType() === 'Point') {
                return $centroid;
            }
        } catch (GeosNotInstalledException $th) {
            //
        }

        // As a rough estimate, we say that the centroid of a colletion is the centroid of it's envelope
        // @@TODO: Make this the centroid of the convexHull
        // Note: Outside of polygons, geometryCollections and the trivial case of points, there is no standard on what a "centroid" is
        return $this->envelope()->centroid();
    }

    /**
     * Attempt to reduce the current collection.
     *
     * @return Geometry|null
     */
    public function reduce(): ?Geometry
    {
        return Geom::reduce($this);
    }

    /**
     * {@inheritDoc}
     */
    public function getBBox(): ?array
    {
        if ($this->isEmpty()) {
            return null;
        }

        try {
            $envelope = $this->geos()->envelope();

            if ($envelope->typeName() === 'Point') {
                return Geom::geosToGeometry($envelope)->getBBOX();
            }

            $geosRing = $envelope->exteriorRing();

            return [
                'maxy' => $geosRing->pointN(3)->getY(),
                'miny' => $geosRing->pointN(1)->getY(),
                'maxx' => $geosRing->pointN(1)->getX(),
                'minx' => $geosRing->pointN(3)->getX(),
            ];
        } catch (GeosNotInstalledException $th) {
            //
        }

        extract($this->components[0]->getBBox());

        // Go through each component and get the max and min x and y

        foreach ($this->components as $component) {
            $componentBbox = $component->getBBox();

            // Do a check and replace on each boundary, slowly growing the bbox
            $maxx = $componentBbox['maxx'] > $maxx ? $componentBbox['maxx'] : $maxx;
            $maxy = $componentBbox['maxy'] > $maxy ? $componentBbox['maxy'] : $maxy;
            $minx = $componentBbox['minx'] < $minx ? $componentBbox['minx'] : $minx;
            $miny = $componentBbox['miny'] < $miny ? $componentBbox['miny'] : $miny;
        }

        return [
            'maxy' => $maxy,
            'miny' => $miny,
            'maxx' => $maxx,
            'minx' => $minx,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function toGeoJsonArray(): array
    {
        $array = [];
        foreach ($this->components as $component) {
            $array[] = $component->toGeoJsonArray();
        }

        return $array;
    }

    /**
     * {@inheritDoc}
     *
     * @uses geos
     */
    public function area(): float
    {
        return $this->tryForwardCallToGeos(
            method: __FUNCTION__,
            fallback: function () {
                $area = 0;
                foreach ($this->components as $component) {
                    $area += $component->area();
                }

                return $area;
            }
        );
    }

    /**
     * Return the count of geometries within the Geometry.
     *
     * @return int
     */
    public function numGeometries(): int
    {
        return count($this->components);
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return $this->numGeometries();
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->components);
    }

    /**
     * Return the N geometry of the Geometry.
     *
     * @param int
     */
    public function geometryN(int $n): ?Geometry
    {
        if (array_key_exists($n - 1, $this->components)) {
            return $this->components[$n - 1];
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function length(): float
    {
        $length = 0;
        foreach ($this->components as $component) {
            $length += $component->length();
        }

        return $length;
    }

    /**
     * {@inheritDoc}
     */
    public function greatCircleLength($radius = 6378137): float
    {
        $length = 0;
        foreach ($this->components as $component) {
            $length += $component->greatCircleLength($radius);
        }

        return $length;
    }

    /**
     * {@inheritDoc}
     */
    public function haversineLength(): float
    {
        $length = 0;
        foreach ($this->components as $component) {
            $length += $component->haversineLength();
        }

        return $length;
    }

    /**
     * {@inheritDoc}
     */
    public function dimension(): int
    {
        $dimension = 0;

        foreach ($this->components as $component) {
            if ($component->dimension() > $dimension) {
                $dimension = $component->dimension();
            }
        }

        return $dimension;
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty(): bool
    {
        if (! count($this->components)) {
            return true;
        }

        foreach ($this->components as $component) {
            if (! $component->isEmpty()) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function numPoints(): int
    {
        $num = 0;
        foreach ($this->components as $component) {
            $num += $component->numPoints();
        }

        return $num;
    }

    /**
     * {@inheritDoc}
     */
    public function getPointsFlatMap(): array
    {
        $points = [];
        foreach ($this->components as $component) {
            $points = array_merge($points, $component->getPointsFlatMap());
        }

        return $points;
    }

    /**
     * {@inheritDoc}
     *
     * @uses geos
     */
    public function equals(Geometry $geometry): bool
    {
        return $this->tryForwardCallToGeos(__FUNCTION__, $geometry, function ($geometry) {

            // To test for equality we check to make sure that there is a matching point
            // in the other geometry for every point in this geometry.
            // This is slightly more strict than the standard, which
            // uses Within(A,B) = true and Within(B,A) = true
            // @@TODO: Eventually we could fix this by using some sort of simplification
            // method that strips redundant vertices (that are all in a row)

            $thisPoints = $this->getPointsFlatMap();
            $otherPoints = $geometry->getPointsFlatMap();

            // First do a check to make sure they have the same number of vertices
            if (count($thisPoints) != count($otherPoints)) {
                return false;
            }

            foreach ($thisPoints as $point) {
                $foundMatch = false;
                foreach ($otherPoints as $key => $testPoint) {
                    if ($point->equals($testPoint)) {
                        $foundMatch = true;
                        unset($otherPoints[$key]);
                        break;
                    }
                }
                if (! $foundMatch) {
                    return false;
                }
            }

            // All points match, return true
            return true;
        });
    }

    /**
     * {@inheritDoc}
     *
     * @uses geos
     */
    public function isSimple(): bool
    {
        return $this->tryForwardCallToGeos(
            method: __FUNCTION__,
            fallback: function () {
                // A collection is simple if all it's components are simple
                foreach ($this->components as $component) {
                    if (! $component->isSimple()) {
                        return false;
                    }
                }

                return true;
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function explode(): ?array
    {
        $parts = [];
        foreach ($this->components as $component) {
            $parts = array_merge($parts, $component->explode());
        }

        return $parts;
    }

    /**
     * Compute the projection of a point onto the line determined by this line segment.
     * Note that the projected point may lie outside the line segment. If this is the
     * case, the projection factor will lie outside the range [0.0, 1.0].
     *
     * @param  \Lyhty\Geometry\Types\Point  $point
     * @param  bool  $normalized
     * @return float
     */
    public function project(Point $point, bool $normalized = false): float
    {
        return $this->boundary()->forwardCallToGeos(__FUNCTION__, [$point, $normalized]);
    }

    /**
     * Checks whether the items are valid to create this collection.
     *
     * @param  array  $items
     */
    protected static function validateItems(array $items)
    {
        static::validateItemCount($items);

        foreach ($items as $item) {
            static::validateItemType($item);
        }
    }

    /**
     * Checks whether the array has enough items to generate a valid WKT.
     *
     * @param  array  $items
     *
     * @see static::$minimumComponentCount
     */
    protected static function validateItemCount(array $items): void
    {
        if (count($items) < static::$minimumComponentCount) {
            $entries = static::$minimumComponentCount === 1 ? 'entry' : 'entries';

            throw new InvalidArgumentException(sprintf(
                '%s must contain at least %d %s',
                static::class,
                static::$minimumComponentCount,
                $entries
            ));
        }
    }

    /**
     * Checks the type of the items in the array.
     *
     * @param $item
     *
     * @see static::$collectionComponentClass
     */
    protected static function validateItemType($item): void
    {
        if (! $item instanceof static::$collectionComponentClass) {
            throw new InvalidArgumentException(sprintf(
                '%s::class must be a collection of %s::class',
                static::class,
                static::$collectionComponentClass
            ));
        }
    }

    public function __call($method, $arguments)
    {
        $get = Str::of(static::$collectionComponentClass)
            ->classBasename()->plural()->start('get')->is($method);

        return $get
            ? $this->getComponents()
            : static::throwBadMethodCallException($method);
    }
}
