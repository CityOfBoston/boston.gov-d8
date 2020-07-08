<?php


namespace Drupal\bos_metrolist\Plugin\WebformHandler;

use Drupal\salesforce\Exception;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use function PHPUnit\Framework\arrayHasKey;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Create a MetroList Listing",
 *   label = @Translation("Create a MetroList Listing"),
 *   category = "MetroList",
 *   description = @Translation("Create a MetroList Listing on SF via a Webform."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class CreateMetroListingWebformHandler extends WebformHandlerBase
{


  /**
   * {@inheritdoc}
   */
  public function client()
  {
    return \Drupal::service('salesforce.client');
  }

  /**
   * {@inheritdoc}
   */
  public function authMan()
  {
    return \Drupal::service('plugin.manager.salesforce.auth_providers');
  }

  /**
   * {@inheritdoc}
   */
  public function getSalesforceUrl()
  {
    return $this->authMan()->getProvider()->getInstanceUrl() . '/' . $this->salesforce_id->value;
  }


  private function contactFieldMappings()
  {
    return [
      'AccountId',
      'LastName',
      'FirstName',
      'Email',
      'Phone',
    ];
//    return [
//      'AccountId' => '0013F00000WotLdQAJ', // @TODO: Make Config?, Hardcoded the SFID to `DND Contacts`
//      'LastName' => 'Jones',
//      'FirstName' => 'Bill',
//      'Email' => 'bill.jones@dev.boston.gov',
//      'Phone' => '(555) 555-5555'
//    ];
  }


  /**
   * @param string $email
   *   Value of Contact email address.
   *
   * @return \Drupal\salesforce\SObject
   *   Object of the requested Salesforce object, Contact that matches the email.
   */

  public function getContactByEmail($email = '')
  {

    $contactSFObject = $this->client()->objectReadbyExternalId('Contact', 'Email', $email);

    return $contactSFObject;
  }

  // @TODO: Change to an upsert?

  /**
   * @param $contactEmail
   * @param string $contactName
   * @param string $contactPhone
   * @return mixed
   */
  public function addContact($contactEmail, $contactName, $contactPhone = null, $contactAddress = [])
  {

    $fieldData = [
      'AccountId' => '0013F00000WotLdQAJ', // @TODO: Make Config?, Hardcoded the SFID to `DND Contacts`
      'Email' => $contactEmail,
    ];

    if ($contactPhone) {
      $fieldData['Phone'] = $contactPhone;
    }

    $contactName = explode(' ', $contactName);
    if (isset($contactName[1])) {
      $fieldData['FirstName'] = $contactName[0];
      $fieldData['LastName'] = $contactName[1];
    } else {
      $fieldData['LastName'] = $contactName[0];
    }

    if (!empty($contactAddress)) {
      $fieldData['MailingStreet'] = $contactAddress['address'];
      $fieldData['MailingCity'] = $contactAddress['city'];
      $fieldData['MailingState'] = $contactAddress['state_province'];
      $fieldData['MailingPostalCode'] = $contactAddress['postal_code'];
    }


    try {
      return $this->client()->objectUpsert('Contact', 'Email', $contactEmail, $fieldData)->__toString();
    } catch (Exception $exception) {
      return false;
    }
  }


  /**
   * @param $developmentName
   * @param $developmentData
   * @param $contactId
   * @return bool
   */
  public function addDevelopment($developmentName, $developmentData, $contactId)
  {

    $fieldData = [
      'Name' => $developmentName,
      'Region__c' => $developmentData['region'] ?? '',
      'Street_Address__c' => $developmentData['street_address'] ?? '',
      'City__c' => $developmentData['city'] ?? '',
      'ZIP_Code__c' => $developmentData['zip_code'] ?? '',
      'Wheelchair_Access__c' => empty($developmentData['wheelchair_accessible']) ? false : true,
      'Listing_Contact_Company__c' => $developmentData['contact_company'] ?? null,
    ];

    if (isset($developmentData['neighborhood'])) {
      $fieldData['Neighborhood__c'] = !empty($developmentData['neighborhood']) ? $developmentData['neighborhood'] : null;
    }

    if (isset($developmentData['utilities_included'])) {
      $fieldData['Utilities_included__c'] = !empty($developmentData['utilities_included']) ? implode(';', $developmentData['utilities_included']) : null;
    }

    if (isset($developmentData['upfront_fees'])) {
      $fieldData['Due_at_signing__c'] = !empty($developmentData['upfront_fees']) ? implode(';', $developmentData['upfront_fees']) : null;
    }

    if (isset($developmentData['utilities_included'])) {
      $fieldData['Features__c'] = !empty($developmentData['amenities_features']) ? implode(';', $developmentData['amenities_features']) : null;
    }

    if (empty($developmentData['same_as_above_contact_info'])) {
      if (!empty($developmentData['public_contact_address'])) {
        $addr = $developmentData['public_contact_address'];
        $fieldData['Public_Contact_Address__c'] = $addr['address'] . "\r\n" . $addr['city'] . ", " . $addr['state_province'] . " " . $addr['postal_code'];
      }
      $fieldData['Public_Contact_Email__c'] = $developmentData['public_contact_email'];
      $fieldData['Public_Contact_Name__c'] = $developmentData['public_contact_name'];
      $fieldData['Public_Contact_Phone__c'] = $developmentData['public_contact_phone'];
    } else {
      if (!empty($developmentData['contact_address'])) {
        $addr = $developmentData['contact_address'];
        $fieldData['Public_Contact_Address__c'] = $addr['address'] . "\r\n" . $addr['city'] . ", " . $addr['state_province'] . " " . $addr['postal_code'];
      }
      $fieldData['Public_Contact_Email__c'] = $developmentData['contact_email'];
      $fieldData['Public_Contact_Name__c'] = $developmentData['contact_name'];
      $fieldData['Public_Contact_Phone__c'] = $developmentData['contact_phone'];
    }


    try {
      return $this->client()->objectUpsert('Development__c', 'Name', $developmentName, $fieldData)->__toString();
    } catch (Exception $exception) {
      return false;
    }
  }


  /**
   * @param $developmentData
   * @param $developmentId
   * @return bool
   */
  public function addUnits($developmentData, $developmentId)
  {

    $units = $developmentData['units'];


    foreach ($units as $unitGroup) {

      for ($unitNumber = 1; $unitNumber <= $unitGroup['unit_count']; $unitNumber++) {

        $unitName = $developmentData['street_address'] . ' Unit #' . $unitNumber;

        //@TODO: Change out the values for some of these by updating the options values in the webform configs to match SF.
        $fieldData = [
          'Name' => $unitName,
          'Development_new__c' => $developmentId,
          'Availability_Status__c' => 'Pending',
          'Income_Restricted_new__c' => $developmentData['units_income_restricted'] ?? 'Yes',
          'Availability_Type__c' => $developmentData['available_how'] == 'first_come_first_serve' ? 'First come, first served' : 'Lottery',
          'User_Guide_Type__c' => $developmentData['available_how'] == 'first_come_first_serve' ? 'First come, first served' : 'Lottery',
          'Occupancy_Type__c' => $developmentData['type_of_listing'] == 'rental' ? 'Rent' : 'Own',
          'Rent_Type__c' => 'Fixed $',
          'Income_Eligibility_AMI_Threshold__c' => isset($unitGroup['ami']) ? $unitGroup['ami'] . '% AMI' : 'N/A',
          'Number_of_Bedrooms__c' => isset($unitGroup['bedrooms']) ? (double)$unitGroup['bedrooms'] : '0.0',
          'Rent_or_Sale_Price__c' => isset($unitGroup['price']) ? (double)filter_var($unitGroup['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : '0.0',
          'ADA_V__c' => empty($unitGroup['ada_v']) ? false : true,
          'ADA_H__c' => empty($unitGroup['ada_h']) ? false : true,
          'ADA_M__c' => empty($unitGroup['ada_m']) ? false : true,
          'Waitlist_Open__c' => empty($developmentData['waitlist_open']) ? false : true,
        ];

        if (isset($unitGroup['bathrooms'])) {
          $fieldData['Number_of_Bedrooms__c'] = (double)$unitGroup['bedrooms'];
        }

        if (isset($unitGroup['minimum_income_threshold'])) {
          $fieldData['Minimum_Income_Threshold__c'] = !empty($unitGroup['minimum_income_threshold']) ? (double)filter_var($developmentData['minimum_income_threshold'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;
        }

        if (isset($developmentData['posted_to_metrolist_date'])) {
          $fieldData['Requested_Publish_Date__c'] = $developmentData['posted_to_metrolist_date'];
        }

        if (isset($developmentData['application_deadline_datetime'])) {
          $fieldData['Lottery_Application_Deadline__c'] = $developmentData['application_deadline_datetime'];
        }

        if (isset($developmentData['website_link'])) {
          $fieldData['Lottery_Application_Website__c'] = $developmentData['website_link'] ?? null;
        }

//        if (isset($unitGroup[''])) {
//          $fieldData[''] = $unitGroup[''];
//        }
//
//        if (isset($developmentData[''])) {
//          $fieldData[''] = $developmentData[''];
//        }


        try {
          $this->client()->objectUpsert('Development_Unit__c', 'Name', $unitName, $fieldData);
        } catch (Exception $exception) {
          return false;
        }


      }

    }


  }


  /**
   * {@inheritdoc}
   */

  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE)
  {

    $fieldData = $webform_submission->getData();

    $contactId = $this->addContact(
      $fieldData['contact_email'],
      $fieldData['contact_name'],
      $fieldData['contact_phone'],
      $fieldData['contact_address']
    );


    if ($contactId) {
      $developmentId = $this->addDevelopment($fieldData['property_name'], $fieldData, $contactId);

      if ($developmentId) {
        $this->addUnits($fieldData, $developmentId);
      }
    }

    parent::postSave($webform_submission, $update); // TODO: Change the autogenerated stub
  }
}
