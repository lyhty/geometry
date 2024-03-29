<?php

namespace Lyhty\Geometry\Adapters;

use Lyhty\Geometry\Types\Geometry;

/**
 * EWKT (Extended Well-Known Text) Adapter.
 */
class Ewkt extends Wkt
{
    /**
     * Serialize geometries into an EWKT string.
     *
     * @return string The Extended-WKT string representation of the input geometries
     */
    public function write(Geometry $geometry)
    {
        $srid = $geometry->SRID();
        $wkt = '';
        if ($srid) {
            $wkt = 'SRID='.$srid.';';
            $wkt .= parent::write($geometry);

            return $wkt;
        }

        return parent::write($geometry);
    }
}
