<?php

namespace Drupal\bos_geocoder\Controller;

use Drupal\bos_core\Controllers\Curl\BosCurlControllerBase;
use Drupal\bos_core\Controllers\Settings\CobSettings;
use Drupal\bos_geocoder\Utility\BosGeoAddress;
use Drupal\Core\Controller\ControllerBase;
use Exception;


/* Note, the ControllerBase class instantiates this class with many core services
 *        pre-loaded as class variables, or available in the container object.
 *        @see ControllerBase.php
 *
 *  class BosGeocoder
 *
 *  david 02 2024
 *  @file docroot/modules/custom/bos_components/modules/bos_geocoder/src/Controller/BosGeocoder.php
 */

class BosGeocoder extends ControllerBase {

  private const GEOCODE_FORWARD = "forward";
  private const GEOCODE_REVERSE = "reverse";

  public const AREA_BOSTON_ONLY = 1;
  public const AREA_MA_ONLY = 2;
  public const AREA_US_ONLY = 4;
  public const AREA_ARCGIS_ONLY = 8;
  public const AREA_GOOGLE_ONLY = 16;
  public const AREA_ANYWHERE = 32;

  private BosGeoAddress $address;
  private array $warnings = [];

  private array $config;

  private int $area;

  public function __construct(BosGeoAddress $address = NULL) {
    $this->address = $address ?? new BosGeoAddress();
    $this->config = CobSettings::getSettings("GEOCODER_SETTINGS", "bos_geocoder", "");
  }

  public function setAddress(BosGeoAddress $address): BosGeocoder {
    $this->address = $address;
    return $this;
  }

  /**
   * Takes the address supplied or the address already stored in $this->address
   * and runs it through the geocoder(s) to get the location (lat/long co-ords).
   * The COB ArcGIS geocoder is first used to determine Boston addresses and if
   * the address is not found in Boston, if the boston_only flag is FALSE then
   * an additional search will be run in the Google geocoder.
   *
   * NOTE: Will return FALSE if address not found in the decoder(s) being used.
   *
   * NOTE: inBoston = False means the address is not found in the COB ArcGIS
   *        geocoder. While this could be because it's not a Boston address, it
   *        also could just be b/c it's not a "findable" address ....
   *
   * @param string|BosGeoAddress|null $address An address as a string or object or FALSE.
   *
   * @return bool|BosGeoAddress An updated address object, or FALSE is no address matched.
   *
   */
  public function geocode(int $area = self::AREA_BOSTON_ONLY):BosGeoAddress|bool {

    $this->area = $area;
    $this->warnings = [];

    // Using the address previously stored in the object
    if (!$this->address->isProcessed()) {
      // Update address using info from the selected geocoder(s)
      if (!$this->lookupLocation()) {
        // The address could not be geocoded.
        // Check $this->>warnings for possible reasons.
        return FALSE;
      }
    }
    else {
      $this->warnings[] = "This address has already been processed.";
    }

    return $this->address;

  }

  /**
   * Takes the lat/long location and tries to reverse geocode into a street
   * address. The COB ArcGIS decoder is used to determine Boston addresses and
   * if the location is determined not to be in Boston, then Google is used to
   * reverse geocode the address.
   * NOTE: will return FALSE if location is not in Boston and $boston_only is
   *      set, but may also return FALSE is the location does not match an
   *      address in the decodcer(s) being used.
   *
   *
   * @param float $lat
   * @param float $long
   * @param bool $boston_only Restricts search to boston geocoder only
   *
   * @return BosGeoAddress|bool An array if the co-ords found, FALSE if the co-ords are
   *                    not found, or if they are not in Boston and the
   *                    $boston_only flag is set.
   */
  public function reverseGeocode(int $area = self::AREA_BOSTON_ONLY): BosGeoAddress|bool {

    $this->area = $area;
    $this->warnings = [];

    if (!$this->reverseLookupLocation()) {
      return FALSE;
    }

    return $this->address;

  }

  /**
   * Lookup the address from co-ordinates loaded into $this->address.
   * If an address is found, it is loaded into the $this->address object and
   * returns TRUE - if not FALSE is returned.
   *
   * The COB geocoder is always used first, and then if no results are found,
   * the Google geocoder is consulted.
   *
   * Check the isInBoston property of $this->address to determine if in Boston.
   *
   * @param bool $arc_gis_only
   *
   * @return bool
   */
  private function lookupLocation(): bool {
    $result = FALSE;
    if (!$this->areaFlag(self::AREA_GOOGLE_ONLY)) {
      $result = $this->lookupArcGISGeocoder(self::GEOCODE_FORWARD);
    }
    if (!$result && !($this->areaFlag(self::AREA_ARCGIS_ONLY))) {
      return !$this->lookupGoogleGeocoder(self::GEOCODE_FORWARD);
    }
    return !empty($result);
  }

  /**
   * Lookup the location (coords) for an address loaded into $this->address.
   * If a location is found, it is loaded into the $this->address object and
   * returns TRUE - if not FALSE is returned.
   *
   * The COB geocoder is always used first, and then if no results are found,
   * the Google geocoder is consulted.
   *
   * Check the isInBoston property of $this->address to determine if in Boston.
   *
   * @param bool $arc_gis_only
   *
   * @return bool
   */
  private function reverseLookupLocation(): bool {
    $result = FALSE;

    if (!($this->area & self::AREA_GOOGLE_ONLY)) {
      $result = $this->lookupArcGISGeocoder(self::GEOCODE_REVERSE);
    }
    if (!$result && !($this->area & self::AREA_ARCGIS_ONLY)) {
      return $this->lookupGoogleGeocoder(self::GEOCODE_REVERSE);
    }
    return !empty($result);
  }

  /**
   * Uses ArcGIS Geocoder to forward or reverse lookup addresses/locations
   * within the City of Boston boundaries.
   *
   * Alters the $this->address object
   *
   * @return BosGeoAddress|bool The Address or FALSE if no match.
   */
  private function lookupArcGISGeocoder(string $direction): BosGeoAddress|bool {

    $config = $this->config["arcgis"];

    $curl = new BosCurlControllerBase([], TRUE);
    $base = $config["base_url"];

    switch ($direction) {
      case self::GEOCODE_FORWARD:
        $endpoint = $config["find_location"];
        $post_fields = "SingleLine=" . $this->address->getValue("singlelineaddress") . "&outFields=PRIMARY_SAM_ID,PRIMARY_SAM_ADDRESS&matchOutOfRange=true&outSR=4326&f=pjson";
        $headers = ["Content-Type" => "application/x-www-form-urlencoded", "Accept" => "application/json"];
        try {
          if ($curl->makeCurl("{$base}/{$endpoint}", $post_fields, $headers, "POST", FALSE)) {
            if ($curl->executeCurl() && $curl->http_code() == 200) {
              $candidate = $this->bestMatch($curl->result()["candidates"],70);
              if (!empty($candidate)) {
                $this->address->update_source = BosGeoAddress::SOURCE_ARCGIS;
                $this->address->setLocation($candidate["location"]["y"], $candidate["location"]["x"]);
                $this->address->setSingleLineAddress($candidate["address"]);
                $this->address->setSamInfo($candidate["attributes"]["PRIMARY_SAM_ID"] ?? "", $candidate["attributes"]["PRIMARY_SAM_ADDRESS"]) ?? "";
                $this->address->setProcessed();
                return $this->address;
              }
              else {
                $this->warnings[] = "Address not found in ArcGIS Geocoder";
                return FALSE;
              }
            }
            else {
              if (empty($curl->result())) {
                $this->warnings[] = "Address not found in ArcGIS Geocoder";
              }
              else {
                $this->warnings[] = "Error requesting from ArcGIS service";
              }
              return FALSE;
            }
          }
          else {
            $this->warnings[] = "Error constructing ArcGIS endpoint request.";
            return FALSE;
          }
        }
        catch (Exception $e) {
          $this->warnings[] = "Error: " . $e->getMessage();
          return FALSE;
        }
        break;

      case self::GEOCODE_REVERSE:
        $endpoint = $config["find_address"];
        $loc = $this->address->location();
        $location = urlencode('{x:' . $loc->long() .',y:' . $loc->lat() . ',spatialReference:{"wkid":4326}}');
        $post_fields = "location=" . $location . "&locationType=street&returnIntersection=false&f=pjson";
        $headers = ["Content-Type" => "application/x-www-form-urlencoded"];
        try {
          if ($curl->makeCurl("{$base}/{$endpoint}", $post_fields, $headers, "POST", FALSE)) {
            if ($curl->executeCurl() && $curl->http_code() == 200) {
              $this->address->update_source = BosGeoAddress::SOURCE_ARCGIS;
              $address = $curl->result()["address"];
              $state = BosGeoAddress::US_STATES[$address["Region"]] ?? $address["Region"];
              $this->address->setAddress(
                $address["Address"],
                $address["Neightborhood"],
                ucwords($address["City"]),
                "",
                $state,
                $address["Postal"],
                $address["CntryName"]
              );
              $this->address->setSingleLineAddress($address["LongLabel"]);
              $this->address->setSamInfo($address["PRIMARY_SAM_ID"],$address["PRIMARY_SAM_ADDRESS"]);
              $this->address->setProcessed();
              return $this->address;
            }
            else {
              if (empty($curl->result())) {
                $this->warnings[] = "Address not found in ArcGIS Geocoder";
              }
              else {
                $this->warnings[] = "Error requesting from ArcGIS service";
              }
              return FALSE;
            }
          }
          else {
            $this->warnings[] = "Error constructing ArcGIS endpoint request.";
            return FALSE;
          }
        }
        catch (Exception $e) {
          $this->warnings[] = "Error: " . $e->getMessage();
          return FALSE;
        }
        break;

      default:
        $this->warnings[] = "Unknown geocoder request" ;
        return FALSE;

    }

  }

  /**
   * Uses Google Geocoder to forward or reverese lookup addresses/locations
   * outside the City of Boston boundaries, but still within the US.
   *
   *  Alters the $this->address object
   *
   * @return BosGeoAddress|bool
   */
  private function lookupGoogleGeocoder(string $direction): BosGeoAddress|bool {

    $config = $this->config["google"];

    $curl = new BosCurlControllerBase([], TRUE);
    $base = $config["base_url"];
    $token = $config["token"];  //"AIzaSyBIJymUhZLfQNNds5zZ6JsEz-tgLfN8qD4";

    switch ($direction) {
      case self::GEOCODE_FORWARD:
        $post_fields = "key=" . $token . "&address=" . urlencode($this->address->singlelineaddress());
        $headers = ["Content-Type" => "application/x-www-form-urlencoded"];
        $endpoint = $config["find_location"];
        try {
          if ($curl->makeCurl("{$base}/{$endpoint}", $post_fields, $headers, "GET", FALSE)) {
            if ($curl->executeCurl() && $curl->http_code() == 200) {
              $this->address->update_source = BosGeoAddress::SOURCE_GOOGLE;
              $result = $curl->result()["results"][0];
              $location = $result["geometry"]["location"];
              $address = $this->parseGoogleAddress($result);
              if (!empty($location)) {
                $this->address->setSingleLineAddress($result["formatted_address"]);
                $this->address->setGooglePlace($result["place_id"]);
                if ($this->areaFlag(self::AREA_BOSTON_ONLY) && !$this->address->isInBoston($address["locality"])) {
                  return FALSE;
                }
                elseif ($this->areaFlag(self::AREA_MA_ONLY) && !$this->address->isInMass($address["administrative_area_level_1"])) {
                  return FALSE;
                }
                elseif ($this->areaFlag(self::AREA_US_ONLY) && !$this->address->isInBoston($address["country"])) {
                  return FALSE;
                }
                $this->address->setLocation($location["lat"], $location["lng"]);
                $this->address->setProcessed();
                return $this->address;
              }
            }
          }
          return FALSE;
        }
        catch (Exception $e) {
          $this->warnings[] = "Error: " . $e->getMessage();
          return FALSE;
        }
        break;

      case self::GEOCODE_REVERSE:
        $loc = $this->address->location();
        $post_fields = "key=" . $token . "&result_type=street_address&latlng=" . $loc->lat() . "," . $loc->long();
        $endpoint = $config["find_address"];
        try {
          if ($curl->makeCurl("{$base}/{$endpoint}", $post_fields, [], "GET", FALSE)) {
            if ($curl->executeCurl() && $curl->http_code() == 200) {
              $this->address->update_source = BosGeoAddress::SOURCE_GOOGLE;
              $result = $curl->result()["results"][0];
              $this->address->setGooglePlace($result["place_id"]);
              $address = $this->parseGoogleAddress($result);
              if ($this->areaFlag(self::AREA_BOSTON_ONLY) && !$this->address->isInBoston($address["locality"])) {
                return FALSE;
              }
              elseif ($this->areaFlag(self::AREA_MA_ONLY) && !$this->address->isInMass($address["administrative_area_level_1"])) {
                return FALSE;
              }
              elseif ($this->areaFlag(self::AREA_US_ONLY) && !$this->address->isInBoston($address["country"])) {
                return FALSE;
              }
              $this->address->setAddress(
                $address["address"],
                $address["neightborhood"],
                ucwords($address["locality"]),
                $address["administrative_area_level_2"],
                $address["administrative_area_level_1"],
                $address["postal_code"],
                $address["country"]
              );
              $this->address->setSingleLineAddress($result["formatted_address"]);
              $this->address->setProcessed();
              return $this->address;
            }
          }
          return FALSE;
        }
        catch (Exception $e) {
          $this->warnings[] = "Error: " . $e->getMessage();
          return FALSE;
        }
        break;

      default:
        $this->warnings[] = "Unknown geocoder request" ;
        return FALSE;

    }
  }

  /**
   * Scans thru' the results from an ArcGIS search and returns the best match.
   *
   * @param array $candidates An array of candidate objects
   * @param int $threshold the minimum score to accept as a valid match
   *
   * @return array The best candidate as an assoc array.
   */
  private function bestMatch(array $candidates, int $threshold = 70) {
    $bestscore = ["score" => 0];
    foreach($candidates as $candidate) {
      if ($candidate["score"] > $bestscore["score"]) {
        $bestscore = [
          "score" => $candidate["score"],
          "candidate" => $candidate
        ];
      }
    }
    if ($bestscore["score"] > $threshold) {
      return $bestscore["candidate"];
    }

    return [];

  }

  /**
   * Process the awkward Google array into a better key:value arrangement.
   *
   * @param array $result
   *
   * @return array
   */
  private function parseGoogleAddress(array $result): array {
    $address = [];
    foreach ($result["address_components"] as $component) {
      foreach ($component["types"] as $type) {
        if ($type != "political") {
          $address[$type] = $component["long_name"];
        }
      }
    };

    $address["address"] = $address["street_number"]??"" . " " . $address["route"];
    unset($address["street_number"]);
    unset($address["route"]);

    return $address;
  }

  /**
   * Check if a particular area flag is set within the $this->area variable.
   *
   * @param integer $flag
   * @return boolean
   */
  private function areaFlag(int $flag): bool  {
    return $this->area & $flag;
  }


}
