<?php

namespace Lyhty\Geometry\Eloquent;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use InvalidArgumentException;
use Lyhty\Geometry\Exceptions\GeometryAttributesNotDefinedException;
use Lyhty\Geometry\Geom;
use Lyhty\Geometry\Query\Builder as QueryBuilder;
use Lyhty\Geometry\Query\GeometryExpression;
use Lyhty\Geometry\Types\Geometry;

/**
 * Trait HasGeometryAttributes.
 *
 * @method static distance($geometryColumn, $geometry, $distance)
 * @method static distanceExcludingSelf($geometryColumn, $geometry, $distance)
 * @method static distanceSphere($geometryColumn, $geometry, $distance)
 * @method static distanceSphereExcludingSelf($geometryColumn, $geometry, $distance)
 * @method static comparison($geometryColumn, $geometry, $relationship)
 * @method static within($geometryColumn, $polygon)
 * @method static crosses($geometryColumn, $geometry)
 * @method static contains($geometryColumn, $geometry)
 * @method static disjoint($geometryColumn, $geometry)
 * @method static equals($geometryColumn, $geometry)
 * @method static intersects($geometryColumn, $geometry)
 * @method static overlaps($geometryColumn, $geometry)
 * @method static doesTouch($geometryColumn, $geometry)
 * @method static orderBySpatial($geometryColumn, $geometry, $orderFunction, $direction = 'asc')
 * @method static orderByDistance($geometryColumn, $geometry, $direction = 'asc')
 * @method static orderByDistanceSphere($geometryColumn, $geometry, $direction = 'asc')
 */
trait HasGeometryAttributes
{
    /*
     * The attributes that are spatial representations.
     * To use this Trait, add the following array to the model class
     *
     * @var array
     *
     * protected array $geometryAttributes = [];
     */

    protected array $geometries = [];

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Lyhty\Geometry\Eloquent\Builder
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Lyhty\Geometry\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        return new QueryBuilder(
            $connection,
            $connection->getQueryGrammar(),
            $connection->getPostProcessor()
        );
    }

    /**
     * Perform a model insert operation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     */
    protected function performInsert(EloquentBuilder $query, array $options = [])
    {
        foreach ($this->attributes as $key => $value) {
            // Preserve the geometry objects prior to the insert
            if ($value instanceof Geometry) {
                $this->geometries[$key] = $value;
                $this->attributes[$key] = new GeometryExpression($value);
            }
        }

        $insert = parent::performInsert($query, $options);

        // Then, let's retrieve the geometry objects so they can be used in the model
        foreach ($this->geometries as $key => $value) {
            $this->attributes[$key] = $value;
        }

        // Return the result of the parent insert.
        return $insert;
    }

    /**
     * Set the array of model attributes. No checking is done.
     *
     * @param  array  $attributes
     * @param  bool  $sync
     * @return $this
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        $geometryAttributes = $this->getGeometryColumns();

        foreach ($attributes as $attribute => &$value) {
            if (in_array($attribute, $geometryAttributes) && is_string($value) && strlen($value) >= 13) {
                $value = Geom::parse($value, 'wkb');
            }
        }

        return parent::setRawAttributes($attributes, $sync);
    }

    /**
     * Get the geometry columns defined for the Model.
     *
     * @return array
     */
    public function getGeometryColumns(): array
    {
        throw_unless(property_exists($this, 'geometryAttributes'), new GeometryAttributesNotDefinedException(sprintf(
            'Property %s::$geometryAttributes is missing for %s::class', __CLASS__, __CLASS__
        )));

        return $this->geometryAttributes;
    }

    /**
     * Return boolean value whether the given column is a "geometry column".
     *
     * @param  string  $column
     * @return true
     *
     * @throws \InvalidArgumentException
     */
    public function isGeometryColumn($column): bool
    {
        return in_array($column, $this->getGeometryColumns())
            ? $column
            : throw new InvalidArgumentException(sprintf(
                "Given column '%s' is not defined in %s::\$geometryAttributes property", $column, __CLASS__
            ));
    }
}
