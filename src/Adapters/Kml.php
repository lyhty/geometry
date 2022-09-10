<?php

namespace Lyhty\Geometry\Adapters;

use DOMDocument;
use Exception;
use InvalidText;
use Lyhty\Geometry\Types\Geometry;
use Lyhty\Geometry\Types\GeometryCollection;
use Lyhty\Geometry\Types\LineString;
use Lyhty\Geometry\Types\Point;
use Lyhty\Geometry\Types\Polygon;

/*
 * Copyright (c) Patrick Hayes
 * Copyright (c) 2010-2011, Arnaud Renevier
 *
 * This code is open-source and licenced under the Modified BSD License.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * PHP Geometry/KML encoder/decoder
 *
 * Mainly inspired/adapted from OpenLayers( http://www.openlayers.org )
 * Openlayers/format/WKT.js
 *
 * @author     Camptocamp <info@camptocamp.com>
 */
class Kml extends GeoAdapter
{
    /**
     * Namespace string. eg 'kml:'
     *
     * @var string
     */
    protected $nss = '';

    /**
     * Read KML string into geometry objects
     *
     * @param  string  $kml A KML string
     * @return Geometry
     */
    public function read($kml)
    {
        return $this->geomFromText($kml);
    }

    /**
     * Serialize geometries into a KML string.
     *
     * @param  Geometry  $geometry
     * @param  string  $namespace [optional]
     * @return string The KML string representation of the input geometries
     */
    public function write(Geometry $geometry, string $namespace = null)
    {
        if ($namespace) {
            $this->nss = $namespace.':';
        }

        return $this->geometryToKML($geometry);
    }

    public function geomFromText($text)
    {
        // Change to lower-case and strip all CDATA
        $text = mb_strtolower($text, mb_detect_encoding($text));
        $text = preg_replace('/<!\[cdata\[(.*?)\]\]>/s', '', $text);

        // Load into DOMDocument
        $xmlobj = new DOMDocument;
        @$xmlobj->loadXML($text);
        if ($xmlobj === false) {
            throw new Exception('Invalid KML: '.$text);
        }

        $this->xmlobj = $xmlobj;
        try {
            $geom = $this->geomFromXML();
        } catch (InvalidText $e) {
            throw new Exception('Cannot Read Geometry From KML: '.$text);
        } catch (Exception $e) {
            throw $e;
        }

        return $geom;
    }

    protected function geomFromXML()
    {
        $geometries = [];
        $geomTypes = $this->factory->lcGeometryList();
        $placemarkElements = $this->xmlobj->getElementsByTagName('placemark');
        if ($placemarkElements->length) {
            foreach ($placemarkElements as $placemark) {
                foreach ($placemark->childNodes as $child) {
                    // Node names are all the same, except for MultiGeometry, which maps to GeometryCollection
                    $nodeName = $child->nodeName == 'multigeometry' ? 'geometrycollection' : $child->nodeName;
                    if (array_key_exists($nodeName, $geomTypes)) {
                        $function = 'parse'.$geomTypes[$nodeName];
                        $geometries[] = $this->$function($child);
                    }
                }
            }
        } else {
            // The document does not have a placemark, try to create a valid geometry from the root element
            $nodeName = $this->xmlobj->documentElement->nodeName == 'multigeometry' ? 'geometrycollection' : $this->xmlobj->documentElement->nodeName;
            if (array_key_exists($nodeName, $geomTypes)) {
                $function = 'parse'.$geomTypes[$nodeName];
                $geometries[] = $this->$function($this->xmlobj->documentElement);
            }
        }

        return $this->factory->reduce($geometries);
    }

    protected function childElements($xml, $nodename = '')
    {
        $children = [];
        if ($xml->childNodes) {
            foreach ($xml->childNodes as $child) {
                if ($child->nodeName == $nodename) {
                    $children[] = $child;
                }
            }
        }

        return $children;
    }

    protected function parsePoint($xml)
    {
        $coordinates = $this->_extractCoordinates($xml);
        if (! empty($coordinates)) {
            return new Point($coordinates[0][0], $coordinates[0][1]);
        } else {
            return new Point;
        }
    }

    protected function parseLineString($xml)
    {
        $coordinates = $this->_extractCoordinates($xml);
        $pointArray = [];
        foreach ($coordinates as $set) {
            $pointArray[] = new Point($set[0], $set[1]);
        }

        return new LineString($pointArray);
    }

    protected function parsePolygon($xml)
    {
        $components = [];

        $outerBoundaryElementA = $this->childElements($xml, 'outerboundaryis');
        if (empty($outerBoundaryElementA)) {
            return new Polygon; // It's an empty polygon
        }
        $outerBoundaryElement = $outerBoundaryElementA[0];
        $outerRingElementA = $this->childElements($outerBoundaryElement, 'linearring');
        $outerRingElement = $outerRingElementA[0];
        $components[] = $this->parseLineString($outerRingElement);

        if (count($components) != 1) {
            throw new Exception('Invalid KML');
        }

        $innerBoundaryElementA = $this->childElements($xml, 'innerboundaryis');
        if (count($innerBoundaryElementA)) {
            foreach ($innerBoundaryElementA as $innerBoundaryElement) {
                foreach ($this->childElements($innerBoundaryElement, 'linearring') as $innerRingElement) {
                    $components[] = $this->parseLineString($innerRingElement);
                }
            }
        }

        return new Polygon($components);
    }

    protected function parseGeometryCollection($xml)
    {
        $components = [];
        $geomTypes = $this->factory->lcGeometryList();
        foreach ($xml->childNodes as $child) {
            $nodeName = ($child->nodeName == 'linearring') ? 'linestring' : $child->nodeName;
            if (array_key_exists($nodeName, $geomTypes)) {
                $function = 'parse'.$geomTypes[$nodeName];
                $components[] = $this->$function($child);
            }
        }

        return new GeometryCollection($components);
    }

    protected function _extractCoordinates($xml)
    {
        $coordElements = $this->childElements($xml, 'coordinates');
        $coordinates = [];
        if (count($coordElements)) {
            $coordSets = explode(' ', preg_replace('/[\r\n]+/', ' ', $coordElements[0]->nodeValue));
            foreach ($coordSets as $setString) {
                $setString = trim($setString);
                if ($setString) {
                    $setArray = explode(',', $setString);
                    if (count($setArray) >= 2) {
                        $coordinates[] = $setArray;
                    }
                }
            }
        }

        return $coordinates;
    }

    private function geometryToKML($geom)
    {
        $type = strtolower($geom->geometryType());
        switch ($type) {
            case 'point':
                return $this->pointToKML($geom);
                break;
            case 'linestring':
                return $this->linestringToKML($geom);
                break;
            case 'polygon':
                return $this->polygonToKML($geom);
                break;
            case 'multipoint':
            case 'multilinestring':
            case 'multipolygon':
            case 'geometrycollection':
                return $this->collectionToKML($geom);
                break;
        }
    }

    private function pointToKML($geom)
    {
        $out = '<'.$this->nss.'Point>';
        if (! $geom->isEmpty()) {
            $out .= '<'.$this->nss.'coordinates>'.$geom->getX().','.$geom->getY().'</'.$this->nss.'coordinates>';
        }
        $out .= '</'.$this->nss.'Point>';

        return $out;
    }

    private function linestringToKML($geom, $type = false)
    {
        if (! $type) {
            $type = $geom->geometryType();
        }

        $str = '<'.$this->nss.$type.'>';

        if (! $geom->isEmpty()) {
            $str .= '<'.$this->nss.'coordinates>';
            $i = 0;
            foreach ($geom->getComponents() as $comp) {
                if ($i != 0) {
                    $str .= ' ';
                }
                $str .= $comp->getX().','.$comp->getY();
                $i++;
            }

            $str .= '</'.$this->nss.'coordinates>';
        }

        $str .= '</'.$this->nss.$type.'>';

        return $str;
    }

    public function polygonToKML($geom)
    {
        $components = $geom->getComponents();
        $str = '';
        if (! empty($components)) {
            $str = '<'.$this->nss.'outerBoundaryIs>'.$this->linestringToKML($components[0], 'LinearRing').'</'.$this->nss.'outerBoundaryIs>';
            foreach (array_slice($components, 1) as $comp) {
                $str .= '<'.$this->nss.'innerBoundaryIs>'.$this->linestringToKML($comp).'</'.$this->nss.'innerBoundaryIs>';
            }
        }

        return '<'.$this->nss.'Polygon>'.$str.'</'.$this->nss.'Polygon>';
    }

    public function collectionToKML($geom)
    {
        $str = '<'.$this->nss.'MultiGeometry>';
        foreach ($geom->getComponents() as $comp) {
            $subAdapter = new KML($this->factory);
            $str .= $subAdapter->write($comp);
        }

        return $str.'</'.$this->nss.'MultiGeometry>';
    }
}
