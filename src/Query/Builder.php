<?php

namespace Lyhty\Geometry\Query;

use BadMethodCallException;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Lyhty\Geometry\Types\Geometry;

class Builder extends QueryBuilder
{
    protected array $stRelations = [
        'within',
        'crosses',
        'contains',
        'disjoint',
        'equals',
        'intersects',
        'overlaps',
        'touches',
    ];

    protected array $stOrderFunctions = [
        'distance',
        'distance_sphere',
    ];

    /**
     * {@inheritDoc}
     */
    public function cleanBindings(array $rawBindings)
    {
        $bindings = [];

        foreach ($rawBindings as &$binding) {
            if ($binding instanceof GeometryExpression) {
                $bindings[] = $binding->getGeometryValue();
                $bindings[] = $binding->getSrid();
            } else {
                $bindings[] = $binding;
            }
        }

        return parent::cleanBindings($bindings);
    }

    public function whereDistance($column, Geometry $geometry, $distance)
    {
        return $this->whereRaw(
            "st_distance(`$column`, st_geomfromtext(?, ?, 'axis-order=long-lat')) <= ?",
            [$geometry->toWKT(), $geometry->SRID(), $distance]
        );
    }

    public function whereDistanceExcludingSelf($column, Geometry $geometry, $distance)
    {
        return $this->whereDistance($column, $geometry, $distance)
            ->whereRaw(
                "st_distance(`$column`, st_geomfromtext(?, ?, 'axis-order=long-lat')) != 0",
                [$geometry->toWKT(), $geometry->SRID()]
            );
    }

    public function selectDistanceValue($column, Geometry $geometry)
    {
        if (!$this->columns) {
            $this->select('*');
        }

        return $this->selectRaw(
            "st_distance(`$column`, st_geomfromtext(?, ?, 'axis-order=long-lat')) as distance",
            [$geometry->toWKT(), $geometry->SRID()]
        );
    }

    public function whereDistanceSphere($column, Geometry $geometry, $distance)
    {
        return $this->whereRaw(
            "st_distance_sphere(`$column`, st_geomfromtext(?, ?, 'axis-order=long-lat')) <= ?",
            [$geometry->toWKT(), $geometry->SRID(), $distance]
        );
    }

    public function whereDistanceSphereExcludingSelf($column, Geometry $geometry, $distance)
    {
        return $this->scopeDistanceSphere($column, $geometry, $distance)
            ->whereRaw(
                "st_distance_sphere(`$column`, st_geomfromtext(?, ?, 'axis-order=long-lat')) != 0",
                [$geometry->toWKT(), $geometry->SRID()]
            );
    }

    public function selectDistanceSphereValue($column, Geometry $geometry)
    {
        if (!$this->columns) {
            $this->select('*');
        }

        return $this->selectRaw(
            "st_distance_sphere(`$column`, st_geomfromtext(?, ?, 'axis-order=long-lat')) as distance",
            [$geometry->toWKT(), $geometry->SRID()]
        );
    }

    protected function spatialComparison($column, Geometry $geometry, $relationship)
    {
        if (!in_array($relationship, $this->stRelations)) {
            throw new BadMethodCallException(sprintf(
                'Call to undefined method %s::%s()', static::class, ''
            ));
        }

        return $this->whereRaw(
            "st_{$relationship}(`$column`, st_geomfromtext(?, ?, 'axis-order=long-lat'))",
            [$geometry->toWKT(), $geometry->SRID()]
        );
    }

    public function whereWithin($column, Geometry $polygon)
    {
        return $this->spatialComparison($column, $polygon, 'within');
    }

    public function whereCrosses($column, Geometry $geometry)
    {
        return $this->spatialComparison($column, $geometry, 'crosses');
    }

    public function whereContains($column, Geometry $geometry)
    {
        return $this->spatialComparison($column, $geometry, 'contains');
    }

    public function whereDisjoint($column, Geometry $geometry)
    {
        return $this->spatialComparison($column, $geometry, 'disjoint');
    }

    public function whereEquals($column, Geometry $geometry)
    {
        return $this->spatialComparison($column, $geometry, 'equals');
    }

    public function whereIntersects($column, Geometry $geometry)
    {
        return $this->spatialComparison($column, $geometry, 'intersects');
    }

    public function whereOverlaps($column, Geometry $geometry)
    {
        return $this->spatialComparison($column, $geometry, 'overlaps');
    }

    public function whereTouches($column, Geometry $geometry)
    {
        return $this->spatialComparison($column, $geometry, 'touches');
    }

    protected function orderBySpatial($column, Geometry $geometry, $orderFunction, $direction = 'asc')
    {
        if (!in_array($orderFunction, $this->stOrderFunctions)) {
            throw new BadMethodCallException(sprintf(
                'Call to undefined method %s::%s()', static::class, ''
            ));
        }

        return $this->orderByRaw(
            "st_{$orderFunction}(`$column`, st_geomfromtext(?, ?, 'axis-order=long-lat')) {$direction}",
            [$geometry->toWKT(), $geometry->SRID()]
        );
    }

    public function orderByDistance($column, Geometry $geometry, $direction = 'asc')
    {
        return $this->orderBySpatial($column, $geometry, 'distance', $direction);
    }

    public function orderByDistanceSphere($column, Geometry $geometry, $direction = 'asc')
    {
        return $this->orderBySpatial($column, $geometry, 'distance_sphere', $direction);
    }
}
