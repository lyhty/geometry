<?php

namespace Lyhty\Geometry\Types;

use Lyhty\Geometry\Contracts\MultiGeometryElement;

/**
 * @method \Lyhty\Geometry\Types\Polygon[] getPolygons()
 * @method \Lyhty\Geometry\Types\Polygon[] getComponents()
 */
class MultiPolygon extends HomogenousCollection implements MultiGeometryElement
{
    /**
     * @var Polygon[]
     */
    protected array $components = [];

    protected static string $geomType = 'MultiPolygon';

    protected static string $collectionComponentClass = Polygon::class;

    protected static int $minimumComponentCount = 1;
}
