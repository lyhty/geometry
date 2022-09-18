<?php

namespace Lyhty\Geometry\Adapters;

use Exception;
use Lyhty\Geometry\Types\Geometry;
use Lyhty\Geometry\Types\LineString;
use Lyhty\Geometry\Types\MultiPoint;
use Lyhty\Geometry\Types\MultiPolygon;
use Lyhty\Geometry\Types\Point;
use Lyhty\Geometry\Types\Polygon;

/*
 * (c) Camptocamp <info@camptocamp.com>
 * (c) Patrick Hayes
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * PHP Google Geocoder Adapter.
 *
 *
 * @author     Patrick Hayes <patrick.d.hayes@gmail.com>
 */
class GoogleGeocode extends GeoAdapter
{
    /**
     * Read an address string or array geometry objects.
     *
     * @param  string  $address  Address to geocode
     * @param  string  $token  Public key to access the API.
     * @param  string  $returnType  Type of Geometry to return. Can either be 'points' or 'bounds' (polygon)
     * @param  Geometry|array[]|null  $bounds  Limit the search area to within this region. For example by default geocoding
     *                                         "Cairo" will return the location of Cairo Egypt. If you pass a polygon of
     *                                         illinois, it will return Cairo IL.
     * @param  bool  $returnMultiple  Return all results in a multipoint or multipolygon
     * @return Geometry
     */
    public function read($address, string $token = '', string $returnType = 'point', $bounds = null, bool $returnMultiple = false)
    {
        if (is_array($address)) {
            $address = implode(',', $address);
        }

        if ($bounds instanceof Geometry) {
            $bounds = $bounds->getBBox();
        }

        $boundsStr = is_array($bounds)
            ? sprintf('%s,%s|%s,%s', $bounds['miny'], $bounds['minx'], $bounds['maxy'], $bounds['maxx'])
            : '';

        $url = sprintf('https://maps.googleapis.com/maps/api/geocode/json?%s', http_build_query([
            'address' => urlencode($address),
            'bounds' => $boundsStr,
            'sensor' => 'false',
            'key' => $token,
        ]));

        $this->result = json_decode(@file_get_contents($url));

        if ($this->result->status === 'OK') {
            if ($returnMultiple === false) {
                if ($returnType === 'point') {
                    return $this->getPoint();
                }
                if ($returnType === 'bounds' || $returnType === 'polygon') {
                    return $this->getPolygon();
                }
            }
            if ($returnMultiple === true) {
                if ($returnType === 'point') {
                    $points = [];
                    foreach ($this->result->results as $delta => $item) {
                        $points[] = $this->getPoint($delta);
                    }

                    return new MultiPoint($points);
                }
                if ($returnType === 'bounds' || $returnType === 'polygon') {
                    $polygons = [];
                    foreach ($this->result->results as $delta => $item) {
                        $polygons[] = $this->getPolygon($delta);
                    }

                    return new MultiPolygon($polygons);
                }
            }
        }

        if ($this->result->status) {
            throw new Exception('Error in Google Geocoder: '.$this->result->status);
        }

        throw new Exception('Unknown error in Google Geocoder');
    }

    /**
     * Writes the geometry into a Google formatted string or an array of address components.
     *
     * @param  \Lyhty\Geometry\Types\Geometry  $geometry
     * @param  string  $token
     * @param  bool  $asArray  Should be either 'string' or 'array'
     * @return string|array
     */
    public function write(Geometry $geometry, string $token = '', $asArray = false)
    {
        $centroid = $geometry->centroid();
        $lat = $centroid->getY();
        $lon = $centroid->getX();

        $url = sprintf('https://maps.googleapis.com/maps/api/geocode/json?%s', http_build_query([
            'latlng' => "$lat,$lon",
            'sensor' => 'false',
            'key' => $token,
        ]));

        $this->result = json_decode(@file_get_contents($url), true);

        if ($this->result->status === 'OK') {
            return $asArray
                ? data_get($this->result, 'results.0.address_components')
                : data_get($this->result, 'results.0.formatted_address');
        }

        if ($this->result->status) {
            throw new Exception('Error in Google Reverse Geocoder: '.$this->result->status);
        }

        throw new Exception('Unknown error in Google Reverse Geocoder');
    }

    private function getPoint($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->location->lat;
        $lon = $this->result->results[$delta]->geometry->location->lng;

        return new Point($lon, $lat);
    }

    private function getPolygon($delta = 0)
    {
        $points = [
            $this->getTopLeft($delta),
            $this->getTopRight($delta),
            $this->getBottomRight($delta),
            $this->getBottomLeft($delta),
            $this->getTopLeft($delta),
        ];
        $outerRing = new LineString($points);

        return new Polygon([$outerRing]);
    }

    private function getTopLeft($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->bounds->northeast->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->southwest->lng;

        return new Point($lon, $lat);
    }

    private function getTopRight($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->bounds->northeast->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->northeast->lng;

        return new Point($lon, $lat);
    }

    private function getBottomLeft($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->bounds->southwest->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->southwest->lng;

        return new Point($lon, $lat);
    }

    private function getBottomRight($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->bounds->southwest->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->northeast->lng;

        return new Point($lon, $lat);
    }
}
