<?php

namespace Lyhty\Geometry\Adapters;

use Lyhty\Geometry\Factory;
use Lyhty\Geometry\Types\Geometry;

/*
 * (c) Patrick Hayes 2011
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * GeoAdapter : abstract class which represents an adapter
 * for reading and writing to and from Geometry objects
 */
abstract class GeoAdapter
{
    protected Factory $factory;

    /**
     * The GeoAdapter constructor.
     *
     * @param  \Lyhty\Geometry\Factory  $factory
     */
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Read input and return a geometry instance.
     *
     * @return Geometry
     */
    abstract public function read($input);

    /**
     * Write out a Geometry instance in the adapter's format.
     *
     * @return mixed
     */
    abstract public function write(Geometry $geometry);
}
