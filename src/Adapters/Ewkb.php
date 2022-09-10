<?php

namespace Lyhty\Geometry\Adapters;

use GeoIO\WKB\Generator\Generator;
use Lyhty\Geometry\Types\Geometry;

/**
 * Ewkb (Extended Well-Known Binary) Adapter
 */
class Ewkb extends Wkb
{
    /**
     * Read WKB binary string into geometry objects
     *
     * @param  string  $wkb An Extended-WKB binary string
     * @param  bool  $isHexString [optional]
     * @return Geometry
     */
    public function read($wkb, bool $isHexString = false)
    {
        return parent::read($wkb, $isHexString);
    }

    /**
     * Serialize geometries into an EWKB binary string.
     *
     * @param  \Lyhty\Geometry\Types\Geometry  $geometry
     * @param  bool  $writeAsHex
     * @param  array  $options
     * @return string The Extended-WKB binary string representation of the input geometries
     */
    public function write(Geometry $geometry, bool $writeAsHex = false, array $options = [])
    {
        return parent::write($geometry, $writeAsHex, ['format' => Generator::FORMAT_EWKB]);
    }
}
