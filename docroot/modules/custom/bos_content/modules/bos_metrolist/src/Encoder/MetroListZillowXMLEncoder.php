<?php

namespace Drupal\bos_metrolist\Encoder;

use Drupal\serialization\Encoder\XmlEncoder as SerializationXMLEncoder;

/**
 * Encodes xml API data.
 *
 * @internal
 */
class MetroListZillowXMLEncoder extends SerializationXMLEncoder {

  /**
   * The formats that this Encoder supports.
   *
   * @var string
   */
  protected static $format = ['metrolist_zillow_xml'];

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {

    $xmlDocument = new \DOMDocument('1.0');
    $xmlDocument->formatOutput = TRUE;
    $xmlDocument->encoding = 'UTF-8';

    $hotPadItems = $xmlDocument->createElement('hotPadsItems');
    $xmlDocument->appendChild($hotPadItems);
    $hotPadItems->setAttribute('version', '2.1');

    $company = $xmlDocument->createElement('Company');
    $companyID = 'city_of_boston_dnd_metrolist';
    $company->setAttribute('id', $companyID);
    $company->appendChild($xmlDocument->createElement('name', 'Boston Metrolist'));
    $company->appendChild($xmlDocument->createElement('city', 'Boston'));
    $company->appendChild($xmlDocument->createElement('state', 'MA'));
    $company->appendChild($xmlDocument->createElement('website', 'https://boston.gov/metrolist'));
    $hotPadItems->appendChild($company);

    foreach ($data as $listingData) {

      $xmlListing = $xmlDocument->createElement('Listing');
      $hotPadItems->appendChild($xmlListing);

      foreach ($listingData as $fieldName => $fieldValue) {

        if ($fieldName == 'id') {
          $xmlListing->setAttribute('id', $fieldValue);
        }

        if ($fieldName == 'street') {
          $xmlListing->setAttribute('hide', 'false');
        }

        // @TODO: Hard coded values, fix this:
        $xmlListing->setAttribute('type', 'RENTAL');
        $xmlListing->setAttribute('companyId', $companyID);
        $xmlListing->setAttribute('propertyType', 'CONDO');

        $xmlField = $xmlDocument->createElement($fieldName, $fieldValue);
        $xmlListing->appendChild($xmlField);
      }
    }

    return $xmlDocument->saveXML();

    // $parent = parent::encode($data, $format, $context);
    // do stuff

    // Return $parent;.
  }

}
