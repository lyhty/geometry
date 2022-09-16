<?php

namespace Lyhty\Geometry\Query;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Query\Expression;
use JsonSerializable;

/**
 * @property \Lyhty\Geometry\Types\Geometry $value
 */
class GeometryExpression extends Expression implements JsonSerializable, Arrayable, Jsonable
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

    public function jsonSerialize(): mixed
    {
        return $this->value->jsonSerialize();
    }

    public function toJson($options = 0)
    {
        return $this->value->toJson($options);
    }

    public function toArray()
    {
        return $this->value->toArray();
    }
}
