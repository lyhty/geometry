<p>
  <img src="https://matti.suoraniemi.com/storage/lyhty-geometry.png" width="400">
</p>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lyhty/geometry.svg?style=flat-square)](https://packagist.org/packages/lyhty/geometry)
[![Total Downloads](https://img.shields.io/packagist/dt/lyhty/geometry.svg?style=flat-square)](https://packagist.org/packages/lyhty/geometry)
[![License](https://img.shields.io/packagist/l/lyhty/geometry.svg?style=flat-square)](https://packagist.org/packages/lyhty/geometry)
<!-- [![Tests](https://img.shields.io/github/workflow/status/lyhty/geometry/Run%20tests?style=flat-square)](https://github.com/lyhty/geometry/actions/workflows/php.yml) -->
<!-- [![StyleCI](https://github.styleci.io/repos/523255216/shield)](https://github.styleci.io/repos/523255216) -->

<!-- CUTOFF -->

This package provides tools to parse and write various formats of Geometry data, with a possibility
to extend it with custom adapters.

The package also provides a Model trait, that adds the OGC standard spatial functions to the Model and
its Query builder.

Many of the package's functionalities relies on [`geos`](https://libgeos.org) being installed on your
system, along with [`php-geos`](https://git.osgeo.org/gitea/geos/php-geos.git) PHP extension. The
package however DOES NOT require this, but many of the features will be unavailable.

## Installation

Install the package with Composer:

    composer require lyhty/geometry

The package registers itself automatically.

## Geometry Models

This package comes with classes for following geometry types:

- `Point`: `Lyhty\Geometry\Types\Point`
- `LineString`: `Lyhty\Geometry\Types\LineString`
- `Polygon`: `Lyhty\Geometry\Types\Polygon`
- `MultiPoint`: `Lyhty\Geometry\Types\MultiPoint`
- `MultiLineString`: `Lyhty\Geometry\Types\MultiLineString`
- `MultiPolygon`: `Lyhty\Geometry\Types\MultiPolygon`
- `GeometryCollection`: `Lyhty\Geometry\Types\GeometryCollection`

All these classes extend an abstract class `Lyhty\Geometry\Types\Geometry`. Additionally,
all classes except for type `Point` extend `Lyhty\Geometry\Types\Collection` class.

With these classes you can easily create your own geometry objects. For example:

```php
$p1 = new Point(0, 4);
$p2 = new Point(1, 5);
$p3 = new Point(2, 6);
$p4 = new Point(3, 7);

$ls1 = new LineString([$p1, $p2]);
$ls2 = new LineString([$p3, $p4]);
$mp = new MultiPoint([$p1, $p2, $p3, $p4]);

$g = new Polygon([$ls1, $ls2]);
$c = new GeometryCollection([$p1, $ls1, $mp, $p1]);
```

### Predicates

> ¹ Feature requires `geos` <br>
> ² Feature utilizes `geos` if available

These models comes with various predicate methods:

- `Geometry::contains(Geometry $geometry): bool`¹
  - Return boolean value whether the geometry contains with the given geometry.
- `Geometry::covers(Geometry $geometry): bool`¹
  - Return boolean value whether the geometry covers the given geometry.
- `Geometry::coveredBy(Geometry $geometry): bool`¹
  - Return boolean value whether the geometry is covered by the given geometry.
- `Geometry::crosses(Geometry $geometry): bool`¹
  - Return boolean value whether the geometry crosses with the given geometry.
- `Geometry::disjoint(Geometry $geometry): bool`¹
  - Return boolean value whether the geometry disjoint with the given geometry.
- `Geometry::equals(Geometry $geometry): bool`¹
  - Return boolean value whether the geometry is “spatially equal” to the given Geometry.
- `Geometry::equalsExact(Geometry $geometry): bool`¹
  - Return boolean value whether this gemometric object is exactly the same as another object,
    including the ordering of component parts.
- `Geometry::intersects(Geometry $geometry): bool`¹
  - Return boolean value whether the geometry intersects with the given geometry.
- `Geometry::overlaps(Geometry $geometry): bool`¹
  - Return boolean value whether the geometry overlaps with the given geometry.
- `Geometry::touches(Geometry $geometry): bool`¹
  - Return boolean value whether the geometry touches with the given geometry.
- `Geometry::within(Geometry $geometry): bool`¹
  - Return boolean value whether the geometry is within the given geometry.

### Operations

> ¹ Feature requires `geos` <br>
> ² Feature utilizes `geos` if available

- `Geometry::area(): float`
  - The area of this Polygon (or GeometryCollection), as measured in the spatial reference system of
    the geometry.
- `Geometry::boundary(): LineString|Point`
  - Returns the closure of the combinatorial boundary of this geometric object.
- `Geometry::buffer(float $distance): Geometry`¹
  - Returns a buffer region around this Geometry having the given width.
- `Geometry::checkValidity(): bool`¹
  - Returns a boolean value whether the geometry is "valid".
- `Geometry::convexHull(): Geometry`
  - Returns a geometric object that represents the convex hull of this geometric object.
- `Geometry::difference(Geometry $geometry): Geometry`
  - Returns a geometric object that represents the Point set difference of this geometric object with
    the given geometry.
- `Geometry::distance(Geometry $geometry): float`
  - Returns the shortest distance between any two Points in the two geometric objects as calculated
    in the spatial reference system of this geometric object.
- `Geometry::envelope(): Polygon`²
  - The minimum bounding box for this Geometry, returned as a Geometry.
- `Geometry::greatCircleLength(): float`
  - <https://en.wikipedia.org/wiki/Great-circle_distance>
- `Geometry::hausdorffDistance(Geometry $geometry): float`¹
  - <http://en.wikipedia.org/wiki/Hausdorff_distance>
- `Geometry::haversineLength(): float`
  - <https://en.wikipedia.org/wiki/Haversine_formula>
- `Geometry::intersection(Geometry $geometry): Geometry`¹
  - Returns a geometric object that represents the point set intersection of this geometric object
    with the given geometry.
- `Geometry::length(): float`
  - Get the length of the geometry in its associated spatial reference.
- `Geometry::relate(Geometry $geometry, $pattern = null)`¹
  - Computes the intersection matrix for the spatial relationship with the given geometry.
- `Geometry::simplify(float $tolerance, bool $preserveTopology = false): Geometry`¹
  - Simplify the geometry using the standard Douglas-Peucker algorithm.
- `Geometry::symDifference(Geometry $geometry): Geometry`¹
  - Returns a geometric object that represents the point set symmetric difference of this geometric
    object with the given geometry.
- `Geometry::union(Geometry|array $geometry)`¹
  - Returns a geometric object that represents the Point set union of this geometric object with
    the given geometry.

### Formatting

- `Geometry::format(string $format, ...$args): mixed`
  - Outputs the geometry into the specified adapter format. See [adapters](#adapters).
- `Geometry::toWKT(): string`
  - Alias to `Geometry::format('wkt')`
- `Geometry::toText(): string`
  - Alias to `Geometry::toWKT()`
- `Geometry::toBinary(bool $asHex = false): string`
  - Alias to `Geometry::format('wkb', $asHes)`
- `Geometry::toArray()`
  - `Arrayable` interface method. Formats the instance to an array that follows the geojson
    structure standard.
- `Geometry::toJson($options = 0)`
  - `Jsonable` interface method. Returns a json string of the geometry in geojson format.
- `Geometry::jsonSerialize(): mixed`

### Other notable methods

- `Geometry::centroid(): Point|null`²
  - The mathematical centroid for this geometry as a Point. For polygons, the result is not
    guaranteed to be interior.
- `Geometry::getBBox(): array|null`²
  - The minimum bounding box for this Geometry, returned as an array.
- `Geometry::pointOnSurface(): Point`¹
  - A Point guaranteed to be within a polygon.
- `Geometry::explode(): array|null`
  - Explode the geometry into an array of LineString instances.

## Geometry Factory

The package also provides a powerful class that helps you parse, write and manipulate geometry data.

The class is accessible through a facade `Lyhty\Geometry\Geom`.

### Parsing & Formatting

The parsing and formatting happens with the help of adapters.

- `Geom::parse($data, $type, ...$otherArgs)`
  - Load from an adapter format (like wkt) into a geometry.
- `Geom::format(Geometry $geometry, $format, ...$otherArgs)`
  - Outputs the geometry into the specified adapter format.

#### Adapters

Out of the box the package supports parsing and writing with following standards and services:

| Name                                     | Parse Syntax                                           | Format Syntax                                        |
|------------------------------------------|--------------------------------------------------------|------------------------------------------------------|
| [WKT](https://bit.ly/3d1F8b8)            | `Geom::parse($data, 'wkt')`                            | `Geom::format($geom, 'wkt')`                         |
| EWKT                                     | `Geom::parse($data, 'ewkt')`                           | `Geom::format($geom, 'ewkt')`                        |
| [WKB](https://bit.ly/3RQTw4J)            | `Geom::parse($data, 'wkb', $isHex = false)`            | `Geom::format($geom, 'wkb', $asHex = false)`         |
| [EWKB](https://bit.ly/3U1ykLc)           | `Geom::parse($data, 'ewkb', $isHex = false)`           | `Geom::format($geom, 'ewkb', $asHex = false)`        |
| [GeoJSON](https://geojson.org)           | `Geom::parse($data, 'geo_json')`                       | `Geom::format($geom, 'geo_json', $asArray = false)`  |
| [KML](https://bit.ly/3RJ9PRg)            | `Geom::parse($data, 'kml')`                            | `Geom::format($geom, 'kml', $namespace = null)`     |
| [GPX](https://bit.ly/2vO2vw2)            | `Geom::parse($data, 'gpx')`                            | `Geom::format($geom, 'gpx', $namespace = null)`     |
| [GeoRSS](https://bit.ly/3RQGO6b)         | `Geom::parse($data, 'geo_rss')`                        | `Geom::format($geom, 'geo_rss', $namespace = null)` |
| [Google Geocode](https://bit.ly/3eGE6lt) | `Geom::parse($address, 'google_geocode', $token = '')` | `Geom::format($geom, 'google_geocode', $token = '')` |
| [GeoHash](https://bit.ly/3eyAl1j)        | `Geom::parse($data, 'geo_hash', $asGrid = false)`      | `Geom::format($geom, 'geo_hash', $precision = null)` |

## `Lyhty\Geometry\Eloquent\HasGeometryAttributes`

Sometimes in our applications we need to have models with geometry columns set to them. This package
provides a trait you can add to your Model, which turns the binary string of a geometry column into
an interactive `Lyhty\Geometry\Types\Geometry` instance! All the methods previously listed are now
usable with the Model!

You have to list the attributes that are geometry in the Model the following way:

```php
class Example extends Model
{
    use HasGeometryAttributes;

    protected array $geometryAttributes = [
        'home_yard_boundary'
    ];
}
```

The trait will override the `Illuminate\Database\Eloquent\Builder` and `Illuminate\Database\Query\Builder`
instances within the Model utilizing the trait with the package's own respective Builder classes.
This is required so the storing of geometry data to the database will work. Also several geometry
query scopes are part of the base query builder.

### Query scopes

- `Builder::selectDistanceValue($column, Geometry $geometry)`
- `Builder::selectDistanceSphereValue($column, Geometry $geometry)`
- `Builder::whereDistance($column, Geometry $geometry, $distance)`
- `Builder::whereDistanceSphere($column, Geometry $geometry, $distance)`
- `Builder::whereDistanceExcludingSelf($column, Geometry $geometry, $distance)`
- `Builder::whereDistanceSphereExcludingSelf($column, Geometry $geometry, $distance)`
- `Builder::whereWithin($column, Geometry $geometry)`
- `Builder::whereCrosses($column, Geometry $geometry)`
- `Builder::whereContains($column, Geometry $geometry)`
- `Builder::whereDisjoint($column, Geometry $geometry)`
- `Builder::whereEquals($column, Geometry $geometry)`
- `Builder::whereIntersects($column, Geometry $geometry)`
- `Builder::whereOverlaps($column, Geometry $geometry)`
- `Builder::whereTouches($column, Geometry $geometry)`
- `Builder::orderByDistance($column, Geometry $geometry, $direction = 'asc')`
- `Builder::orderByDistanceSphere($column, Geometry $geometry, $direction = 'asc')`
