<?php

namespace Lyhty\Geometry\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Traits\ForwardsCalls;
use Lyhty\Geometry\Exceptions\GeosNotInstalledException;
use Lyhty\Geometry\Geom;
use Lyhty\Geometry\Types\Geometry;

trait InteractsWithGeos
{
    use ForwardsCalls;

    private $geos = null;

    /**
     * Return the instance as a geos geometry instance.
     *
     * @return (\GEOSGeometry&\Lyhty\Geometry\GeosGeometryWrapper)|null
     */
    public function geos()
    {
        if (! Geom::geosInstalled()) {
            throw new GeosNotInstalledException;
        }

        // It hasn't been set yet, generate it
        if (! $this->geos) {
            $this->geos = Geom::makeWKBReader()->readHEX($this->format('wkb', true));
        }

        return $this->geos;
    }

    /**
     * Set a GEOSGeometry object representing this geometry.
     *
     * @param  \GEOSGeometry  $geos
     * @return void
     */
    public function setGeos($geos): void
    {
        $this->geos = $geos;
    }

    /**
     * Call geos and get the response conditionally converted back to native geometry.
     *
     * @param  string  $method
     * @param  array|mixed  $arguments
     * @return \Lyhty\Geometry\Types\Geometry|mixed
     */
    protected function forwardCallToGeos($method, $arguments = []): mixed
    {
        $arguments = Arr::wrap($arguments);

        collect($arguments)
            ->whereInstanceOf(Geometry::class)
            ->each(function (Geometry $value, $index) use (&$arguments) {
                $arguments[$index] = $value->geos();
            });

        $result = $this->forwardCallTo($this->geos(), $method, $arguments);

        return Geom::isGeos($result)
            ? Geom::geosToGeometry($result)
            : $result;
    }

    /**
     * Attempt to call geos method, and if that fails, run the fallback.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @param  mixed  $fallback
     * @return mixed
     */
    protected function tryForwardCallToGeos($method, $arguments = [], $fallback = null)
    {
        try {
            return $this->forwardCallToGeos($method, $arguments);
        } catch (GeosNotInstalledException $th) {
        }

        return value($fallback, ...$arguments);
    }
}
