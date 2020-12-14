<?php

namespace Drupal\bos_metrolist\Encoder;


use Drupal\serialization\Encoder\XmlEncoder as SerializationXMLEncoder;

/**
 * Encodes xml API data.
 *
 * @internal
 */
class MetroListZillowXMLEncoder extends SerializationXMLEncoder
{

  /**
   * The formats that this Encoder supports.
   *
   * @var string
   */
  protected static $format = ['metrolist_zillow_xml'];

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = [])
  {

    $XMLDocument = new \DOMDocument('1.0');
    $XMLDocument->formatOutput = true;
    $XMLDocument->encoding = 'UTF-8';

    $hotPadItems = $XMLDocument->createElement('hotPadsItems');
    $XMLDocument->appendChild($hotPadItems);
    $hotPadItems->setAttribute('version', '2.1');

    $company = $XMLDocument->createElement('Company');
    $companyID = 'city_of_boston_dnd_metrolist';
    $company->setAttribute('id', $companyID);
    $company->appendChild($XMLDocument->createElement('name', 'Boston Metrolist'));
    $company->appendChild($XMLDocument->createElement('city', 'Boston'));
    $company->appendChild($XMLDocument->createElement('state', 'MA'));
    $company->appendChild($XMLDocument->createElement('website', 'https://boston.gov/metrolist'));
    $hotPadItems->appendChild($company);


    foreach ($data as $listingData) {

      $XMLListing = $XMLDocument->createElement('Listing');
      $hotPadItems->appendChild($XMLListing);

      foreach ($listingData as $fieldName => $fieldValue) {

        if ($fieldName == 'id') {
          $XMLListing->setAttribute('id', $fieldValue);
        }

        if ($fieldName == 'street') {
          $XMLListing->setAttribute('hide', 'false');
        }

        //@TODO: Hard coded values, fix this:
        $XMLListing->setAttribute('type', 'RENTAL');
        $XMLListing->setAttribute('companyId', $companyID);
        $XMLListing->setAttribute('propertyType', 'CONDO');


        $XMLField = $XMLDocument->createElement($fieldName, $fieldValue);
        $XMLListing->appendChild($XMLField);
      }
    }

    return $XMLDocument->saveXML();







//    $parent = parent::encode($data, $format, $context);
// do stuff







//    return $parent;
  }
}
