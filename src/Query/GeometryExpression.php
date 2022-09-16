<?php

namespace Lyhty\Geometry\Query;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Traits\ForwardsCalls;
use JsonSerializable;

/**
 * @mixin \Lyhty\Geometry\Types\Geometry
 *
 * @property \Lyhty\Geometry\Types\Geometry $value
 */
class GeometryExpression extends Expression implements JsonSerializable, Arrayable, Jsonable
{
    use ForwardsCalls;

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

    public function __call($name, $arguments)
    {
        return $this->forwardCallTo($this->value, $name, $arguments);
    }
}
