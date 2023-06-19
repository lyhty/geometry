<?php

namespace Lyhty\Geometry\Types;

/**
 * @method \Lyhty\Geometry\Types\Geometry[] getGeometries()
 */
class GeometryCollection extends Collection
{
    protected static string $geomType = 'GeometryCollection';

    protected static string $collectionComponentClass = Geometry::class;

    protected static int $minimumComponentCount = 0;

    /**
     * Get the given geometry as an array of geojson components (recursive).
     *
     * @return array
     */
    public function getGeoJsonDataKey(): string
    {
        return 'geometries';
    }

    /**
     * We need to override toGeoJsonArray. Because geometry collections are heterogeneous
     * we need to specify which type of geometries they contain. We need to do this
     * because, for example, there would be no way to tell the difference between a
     * MultiPoint or a LineString, since they share the same structure (collection
     * of points). So we need to call out the type explicitly.
     */
    public function toGeoJsonArray(): array
    {
        $array = [];

        foreach ($this->components as $component) {
            $array[] = $component->toArray();
        }

        return $array;
    }
}
