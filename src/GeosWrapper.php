<?php

namespace Lyhty\Geometry;

/**
 * Wrapper class essentially to hide GEOS class not found errors.
 */
final class GeosWrapper
{
    protected const GEOS_EXTENSION_NAME = 'geos';

    protected const GEOS_GEOMETRY_CLASS = 'GEOSGeometry';

    protected const GEOS_WKB_WRITER_CLASS = 'GEOSWKBWriter';

    protected const GEOS_WKB_READER_CLASS = 'GEOSWKBReader';

    protected const GEOS_WKT_WRITER_CLASS = 'GEOSWKTWriter';

    protected const GEOS_WKT_READER_CLASS = 'GEOSWKTReader';

    /**
     * Return boolean value whether geos is installed.
     */
    public function geosInstalled(): bool
    {
        return extension_loaded($this::GEOS_EXTENSION_NAME);
    }

    /**
     * Create an instance of GEOSGeometry.
     */
    public function makeGeometry(): object
    {
        return app($this::GEOS_GEOMETRY_CLASS);
    }

    /**
     * Create an instance of GEOSWKTWriter.
     */
    public function makeWKTWriter(): object
    {
        return app($this::GEOS_WKT_WRITER_CLASS);
    }

    /**
     * Create an instance of GEOSWKTReader.
     */
    public function makeWKTReader(): object
    {
        return app($this::GEOS_WKT_READER_CLASS);
    }

    /**
     * Create an instance of GEOSWKBWriter.
     */
    public function makeWKBWriter(): object
    {
        return app($this::GEOS_WKB_WRITER_CLASS);
    }

    /**
     * Create an instance of GEOSWKBReader.
     */
    public function makeWKBReader(): object
    {
        return app($this::GEOS_WKB_READER_CLASS);
    }

    /**
     * Return boolean value whether the given value is a geos instance.
     *
     * @param  mixed  $value
     */
    public function isGeos($value): bool
    {
        return is_object($value)
            && get_class($value) === $this::GEOS_GEOMETRY_CLASS;
    }
}
