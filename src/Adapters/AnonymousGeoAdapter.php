<?php

namespace Lyhty\Geometry\Adapters;

use Closure;
use Lyhty\Geometry\Factory;
use Lyhty\Geometry\Types\Geometry;

class AnonymousGeoAdapter extends GeoAdapter
{
    public function __construct(
        Factory $factory,
        protected Closure $read,
        protected Closure $write
    ) {
        parent::__construct($factory);
    }

    /**
     * {@inheritDoc}
     */
    public function read($input, ...$args)
    {
        return call_user_func($this->read, $input, ...$args);
    }

    /**
     * {@inheritDoc}
     */
    public function write(Geometry $geometry, ...$args)
    {
        return call_user_func($this->write, $geometry, ...$args);
    }
}
