<?php

namespace Lyhty\Geometry\Concerns;

use Lyhty\Geometry\Geom;

trait Formatting
{
    /**
     * Outputs the geometry into the specified adapter format.
     *
     * @param  string  $format
     * @param  mixed[]  ...$args
     * @return mixed
     */
    public function format(string $format, ...$args): mixed
    {
        return Geom::format($this, $format, ...$args);
    }

    /**
     * Alias for `Geom::formatWkt($this)`.
     *
     * @return string
     */
    public function toWKT(): string
    {
        return Geom::formatWkt($this);
    }

    /**
     * Alias for `static::toWKT()`.
     *
     * @return string
     */
    public function toText(): string
    {
        return $this->toWKT();
    }

    /**
     * Alias for `Geom::formatWkb($this, $asHex)`.
     *
     * @param  bool  $asHex
     * @return string
     */
    public function toBinary(bool $asHex = false): string
    {
        return Geom::formatWkb($this, $asHex);
    }

    /**
     * Get the geojson data key name.
     *
     * @return string
     */
    abstract public function getGeoJsonDataKey(): string;

    /**
     * Get the given geometry as an array of geojson components (recursive).
     *
     * @return array
     */
    abstract public function toGeoJsonArray(): array;

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return [
            'type' => $this->geometryType(),
            $this->getGeoJsonDataKey() => $this->toGeoJsonArray(),
        ];
    }

    /**
     * Alias for `static::format('geo_json')`.
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
