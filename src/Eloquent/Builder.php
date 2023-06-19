<?php

namespace Lyhty\Geometry\Eloquent;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Lyhty\Geometry\Query\GeometryExpression;
use Lyhty\Geometry\Types\Geometry;

/**
 * @method \Lyhty\Geometry\Query\Builder newQuery()
 * @method \Lyhty\Geometry\Query\Builder toBase()
 * @method \Lyhty\Geometry\Query\Builder getQuery()
 *
 * @mixin \Lyhty\Geometry\Query\Builder
 */
class Builder extends EloquentBuilder
{
    /**
     * The base query builder instance.
     *
     * @var \Lyhty\Geometry\Query\Builder
     */
    protected $query;

    /**
     * {@inheritDoc}
     */
    public function update(array $values)
    {
        foreach ($values as &$value) {
            if ($value instanceof Geometry) {
                $value = $this->asWKT($value);
            }
        }

        return parent::update($values);
    }

    /**
     * Wrap the geometry inside an expression.
     */
    protected function asWKT(Geometry $geometry): GeometryExpression
    {
        return new GeometryExpression($geometry);
    }
}
