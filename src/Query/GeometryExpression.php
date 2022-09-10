<?php

namespace Lyhty\Geometry\Query;

use Illuminate\Database\Query\Expression;

/**
 * @property \Lyhty\Geometry\Types\Geometry $value
 */
class GeometryExpression extends Expression
{
    public function getValue()
    {
        return "ST_GeomFromText(?, ?, 'axis-order=long-lat')";
    }

    public function getGeometryValue()
    {
        return $this->value->toWKT();
    }

    public function getSrid()
    {
        return $this->value->SRID();
    }
}
