<?php

namespace Lyhty\Geometry\Types;

use Lyhty\Geometry\Contracts\MultiGeometryElement;

/**
 * @method \Lyhty\Geometry\Types\Polygon[] getPolygons()
 * @method \Lyhty\Geometry\Types\Polygon[] getComponents()
 */
class MultiPolygon extends HomogenousCollection implements MultiGeometryElement
{
    protected static string $geomType = 'MultiPolygon';

    protected static string $collectionComponentClass = Polygon::class;

    protected static int $minimumComponentCount = 1;
}
