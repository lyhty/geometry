<?php

namespace Lyhty\Geometry;

use Closure;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use JsonSerializable;
use Lyhty\Geometry\Adapters\AnonymousGeoAdapter;
use Lyhty\Geometry\Adapters\GeoAdapter;
use Lyhty\Geometry\Contracts\MultiGeometryElement;
use Lyhty\Geometry\Contracts\SingleGeometryElement;
use Lyhty\Geometry\Types\Geometry;
use Lyhty\Geometry\Types\GeometryCollection;
use RuntimeException;
use TypeError;

/**
 * @method bool geosInstalled()
 * @method \GEOSGeometry makeGeometry()
 * @method \GEOSWKTReader makeWKTReader()
 * @method \GEOSWKTWriter makeWKTWriter()
 * @method \GEOSWKBReader makeWKBReader()
 * @method \GEOSWKBWriter makeWKBWriter()
 * @method bool isGeos(mixed $value)
 * @method \Lyhty\Geometry\Types\Geometry|null parseWkt($data)
 * @method \Lyhty\Geometry\Types\Geometry|null parseEwkt($data)
 * @method \Lyhty\Geometry\Types\Geometry|null parseWkb($data, bool $isHex = false)
 * @method \Lyhty\Geometry\Types\Geometry|null parseEwkb($data, bool $isHex = false)
 * @method \Lyhty\Geometry\Types\Geometry|null parseGeoJson($data)
 * @method \Lyhty\Geometry\Types\Geometry|null parseKml($data)
 * @method \Lyhty\Geometry\Types\Geometry|null parseGpx($data)
 * @method \Lyhty\Geometry\Types\Geometry|null parseGeoRss($data)
 * @method \Lyhty\Geometry\Types\Geometry|null parseGoogleGeocode($address, string $token = '')
 * @method \Lyhty\Geometry\Types\Geometry|null parseGeoHash($data, bool $asGrid = false)
 * @method string formatWkt($geom)
 * @method string formatEwkt($geom)
 * @method string formatWkb($geom, bool $asHex = false)
 * @method string formatEwkb($geom, bool $asHex = false)
 * @method string formatGeoJson($geom, bool $asArray = false)
 * @method string formatKml($geom, string $namespace = null)
 * @method string formatGpx($geom, string $namespace = null)
 * @method string formatGeoRss($geom, string $namespace = null)
 * @method string formatGoogleGeocode($geom, string $token = '')
 * @method string formatGeoHash($geom, bool $precision = null)
 */
class Factory
{
    use ForwardsCalls;

    /**
     * @var string[]
     */
    public static $adapters = [
        Adapters\Wkt::class,
        Adapters\Ewkt::class,
        Adapters\Wkb::class,
        Adapters\Ewkb::class,
        Adapters\GeoJson::class,
        Adapters\Kml::class,
        Adapters\Gpx::class,
        Adapters\GeoRss::class,
        Adapters\GoogleGeocode::class,
        Adapters\GeoHash::class,
    ];

    /**
     * @var string[]
     */
    protected array $extensions = [];

    /**
     * @var array<Closure,Closure>[]
     */
    protected array $closureExtensions = [];

    /**
     * @var string[]
     */
    protected static $geometries = [
        Types\Point::class,
        Types\LineString::class,
        Types\Polygon::class,
        Types\MultiPoint::class,
        Types\MultiLineString::class,
        Types\MultiPolygon::class,
        Types\GeometryCollection::class,
    ];

    public function __construct(
        protected GeosWrapper $geos
    ) {
    }

    /**
     * Load from an adapter format (like wkt) into a geometry. The first argument is
     * the data, the second one is the format of the data. All additional arguments
     * are passed along to the read method of the relevant adapter.
     *
     * @param  mixed  $data
     * @param  string|null  $type
     * @param  mixed[]  ...$otherArgs
     * @return \Lyhty\Geometry\Types\Geometry|null
     */
    public function parse(mixed $data, ?string $type = null, ...$otherArgs): ?Geometry
    {
        // If the user is trying to parse a Geometry from a Geometry... Just pass it back
        if ($data instanceof Geometry) {
            return $data;
        }

        // Auto-detect type if needed
        if (is_null($type)) {
            if ($data instanceof Jsonable) {
                $data = $data->toJson();
            }

            if ($data instanceof JsonSerializable) {
                $data = json_encode($data);
            }

            $detected = $this->detectFormat($data);
            if (! $detected) {
                return null;
            }

            $format = explode(':', $detected);
            $type = array_shift($format);
            $otherArgs = array_merge($format, $otherArgs);
        }

        $processor = $this->makeAdapter($type);

        // Data is not an array, just pass it normally
        if (! is_array($data)) {
            return $processor->read($data, ...$otherArgs);
        }

        // Data is an array, combine all passed in items into a single geomtetry
        $geoms = [];

        foreach ($data as $item) {
            $geoms[] = $processor->read($item, ...$otherArgs);
        }

        return $this->reduce($geoms);
    }

    /**
     * Outputs the geometry into the specified adapter format.
     *
     * @param  \Lyhty\Geometry\Types\Geometry  $geometry
     * @param  string  $format
     * @param  mixed[]  ...$otherArgs
     * @return string
     */
    public function format(Geometry $geometry, string $format, ...$otherArgs)
    {
        return $this->makeAdapter($format)->write($geometry, ...$otherArgs);
    }

    /**
     * Wrap the given geometry instances to corresponding collection type.
     *
     * @param  array|\Lyhty\Geometry\Types\Geometry  $geometries
     * @return \Lyhty\Geometry\Types\Geometry|null
     */
    public function wrap(array|Geometry $geometries): ?Geometry
    {
        $geometries = Arr::wrap($geometries);
        $geometryTypes = collect($geometries)->map->geometryType()->unique();

        if ($geometryTypes->isEmpty()) {
            return null;
        }

        if (! $geometryTypes->containsOneItem()) {
            return new GeometryCollection($geometries);
        }

        if (count($geometries) === 1) {
            return $geometries[0];
        }

        $class = $this->geometryList()['Multi'.$geometryTypes->first()];

        return new $class($geometries);
    }

    /**
     * Convert geos geometry to native geometry.
     *
     * @param  \GEOSGeometry&GeosGeometryWrapper  $geos
     * @return \Lyhty\Geometry\Types\Geometry|null
     */
    public function geosToGeometry($geos): ?Geometry
    {
        if (! $this->geosInstalled()) {
            return null;
        }

        $wkbWriter = $this->makeWKBWriter();
        $wkb = $wkbWriter->writeHEX($geos);

        $geometry = $this->parse($wkb, 'wkb', true);

        if ($geometry) {
            $geometry->setGeos($geos);

            return $geometry;
        }

        return null;
    }

    /**
     * Reduce a geometry, or an array of geometries, into their 'lowest' available
     * common geometry. For example a GeometryCollection of only points will become
     * a MultiPoint. A multi-point containing a single point will return a point.
     * An array of geometries can be passed and they will be compiled into a single
     * geometry.
     *
     * @param  \Lyhty\Geometry\Types\Geometry|\Lyhty\Geometry\Types\Geometry[]  $geometry
     * @return \Lyhty\Geometry\Types\Geometry|null
     */
    public function reduce(Geometry|array $geometry): ?Geometry
    {
        // If it's an array of one, then just parse the one
        if (is_array($geometry)) {
            if (! $geometry) {
                return null;
            }

            if (count($geometry) === 1) {
                return $this->reduce(array_shift($geometry));
            }
        }

        if ($geometry instanceof Geometry) {
            // If the geometry cannot even theoretically be reduced more, then pass it back
            if ($geometry instanceof SingleGeometryElement) {
                return $geometry;
            }

            // If it is a multi-geometry, check to see if it just has one member
            // If it does, then pass the member, if not, then just pass back the geometry
            if ($geometry instanceof MultiGeometryElement) {
                return count($components = $geometry->getComponents()) === 1
                    ? $components[0]
                    : $geometry;
            }
        }

        // So now we either have an array of geometries, a GeometryCollection,
        // or an array of GeometryCollections.
        $geometry = Arr::wrap($geometry);
        $geometries = [];

        foreach ($geometry as $item) {
            if (! $item) {
                continue;
            }

            if (! $item instanceof SingleGeometryElement) {
                foreach ($item->getComponents() as $component) {
                    $geometries[] = $component;
                }
            } else {
                $geometries[] = $item;
            }
        }

        return $this->wrap($geometries);
    }

    /**
     * Detect a format of given value. This function is meant to be SPEEDY. It could
     * make a mistake in XML detection if you are mixing or using namespaces in weird
     * ways (ie, KML inside an RSS feed).
     *
     * @param  mixed  &$input
     * @return string|null
     */
    public function detectFormat(&$input): ?string
    {
        $mem = fopen('php://memory', 'r+');
        fwrite($mem, $input ?? '', 11); // Write 11 bytes - we can detect the vast majority of formats in the first 11 bytes
        fseek($mem, 0);

        $bytes = unpack('c*', fread($mem, 11));

        // If bytes is empty, then we were passed empty input
        if (empty($bytes)) {
            return false;
        }

        // First char is a tab, space or carriage-return. trim it and try again
        if ($bytes[1] === 9 || $bytes[1] === 10 || $bytes[1] === 32) {
            $ltinput = ltrim($input);

            return $this->detectFormat($ltinput);
        }

        // Detect WKB or EWKB -- first byte is 1 (little endian indicator) or 0
        if ($bytes[1] === 1 || $bytes[1] === 0) {
            // If SRID byte is TRUE (1), it's EWKB
            if ($bytes[5]) {
                return 'ewkb';
            }

            return 'wkb';
        }

        // Detect HEX encoded WKB or EWKB (PostGIS format) -- first byte is 48, second byte is 49 (hex '01' => first-byte = 1)
        if ($bytes[1] === 48 && $bytes[2] === 49) {
            // The shortest possible WKB string (LINESTRING EMPTY) is 18 hex-chars (9 encoded bytes) long
            // This differentiates it from a geohash, which is always shorter than 18 characters.
            if (strlen($input) >= 18) {
                //@@TODO: Differentiate between EWKB and WKB -- check hex-char 10 or 11 (SRID bool indicator at encoded byte 5)
                return 'ewkb:1';
            }
        }

        // Detect GeoJSON - first char starts with {
        if ($bytes[1] === 123) {
            return 'geo_json';
        }

        // Detect EWKT - first char is S
        if ($bytes[1] === 83) {
            return 'ewkt';
        }

        // Detect WKT - first char starts with P (80), L (76), M (77), or G (71)
        $wktChars = [80, 76, 77, 71];
        if (in_array($bytes[1], $wktChars)) {
            return 'wkt';
        }

        // Detect XML -- first char is <
        if ($bytes[1] === 60) {
            // grab the first 256 characters
            $string = substr($input, 0, 256);

            if (strpos($string, '<kml') !== false) {
                return 'kml';
            }
            if (strpos($string, '<coordinate') !== false) {
                return 'kml';
            }
            if (strpos($string, '<gpx') !== false) {
                return 'gpx';
            }
            if (strpos($string, '<georss') !== false) {
                return 'geo_rss';
            }
            if (strpos($string, '<rss') !== false) {
                return 'geo_rss';
            }
            if (strpos($string, '<feed') !== false) {
                return 'geo_rss';
            }
        }

        // We need an 8 byte string for geohash and unpacked WKB / WKT
        fseek($mem, 0);
        $string = trim(fread($mem, 8));

        // Detect geohash - geohash ONLY contains lowercase chars and numerics
        preg_match('/[a-z0-9]+/', $string, $matches);
        if ($matches[0] === $string) {
            return 'geo_hash';
        }

        return null;
    }

    /**
     * Forward calls to GeosWrapper.
     *
     * @param  string  $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if ((bool) $method = Str::match('/^parse|format/', $name)) {
            $type = Str::of($name)->after($method)->studly();

            return count($arguments) > 0
                ? $this->{$method}(array_shift($arguments), $type, ...$arguments)
                : throw new TypeError(sprintf(
                    'Too few arguments to function %s::%s(), 0 passed in %s and at least 1 expected', __CLASS__, $method, __FILE__
                ));
        }

        return $this->forwardCallTo($this->geos, $name, $arguments);
    }

    /**
     * Get the list of geometry classes mapped with their geometry type set as key.
     *
     * @return array
     */
    public function geometryList(): array
    {
        $types = array_map(
            fn (string $class) => $class::geometryType(),
            $this::$geometries
        );

        return array_combine($types, $this::$geometries);
    }

    /**
     * Get the list of geometry types with the key being the type lowercased.
     *
     * @return array
     */
    public function lcGeometryList(): array
    {
        $types = array_keys($this->geometryList());
        $lcTypes = array_map(fn ($type) => strtolower($type), $types);

        return array_combine($lcTypes, $types);
    }

    /**
     * Get the list of adapters with the class name set as the key.
     *
     * @return array
     */
    public function adapters(): array
    {
        $types = array_map(
            fn (string $class) => class_basename($class),
            $this::$adapters
        );

        return array_combine($types, $this::$adapters);
    }

    /**
     * Make an adapter of given type.
     *
     * @param  string  $type
     * @return \Lyhty\Geometry\Adapters\GeoAdapter
     *
     * @throws \RuntimeException
     */
    public function makeAdapter(string $type): GeoAdapter
    {
        if (array_key_exists($studType = Str::studly($type), $this->adapters())) {
            $class = $this->adapters()[$studType];

            return new $class($this);
        }

        return $this->makeExtensionAdapter($type)
            ?? $this->makeClosureExtensionAdapter($type)
            ?? throw new RuntimeException(sprintf('Adapter for type %s could not be found.', $type));
    }

    /**
     * Make an adapter instance from an extension adapter.
     *
     * @param  string  $type
     * @return void
     */
    protected function makeExtensionAdapter(string $type)
    {
        if (! array_key_exists($type, $this->extensions)) {
            return null;
        }

        $class = $this->extensions[$type];

        return new $class($this);
    }

    /**
     * Make a closure based extension adapter.
     *
     * @param  string  $type
     * @return \Lyhty\Geometry\Adapters\AnonymousGeoAdapter|null
     */
    protected function makeClosureExtensionAdapter(string $type): ?AnonymousGeoAdapter
    {
        if (! array_key_exists($type, $this->closureExtensions)) {
            return null;
        }

        return new AnonymousGeoAdapter($this, ...$this->closureExtensions[$type]);
    }

    /**
     * Register a custom adapter extension.
     *
     * @param  string  $adapter
     * @param  \Closure|string  $extension  (or the `read` closure)
     * @param  \Closure|null  $write
     * @param  string|null  $message
     * @return void
     */
    public function extend($adapter, $extension, Closure $write = null): void
    {
        if ($write instanceof Closure) {
            if (! $extension instanceof Closure) {
                throw new RuntimeException('Both write and read parameters must be valid closures');
            }

            $this->closureExtensions[$adapter] = [$extension, $write];

            return;
        }

        if (! in_array(GeoAdapter::class, class_parents($extension))) {
            throw new RuntimeException('Invalid extension class given');
        }

        $this->extensions[$adapter] = $extension;
    }
}
