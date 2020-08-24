<?php

namespace Drupal\bos_metrolist\Plugin\WebformHandler;

use Drupal\salesforce\Exception;
use Drupal\salesforce\SelectQuery;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\bos_metrolist\MetroListSalesForceConnection;

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
   * @var bool
   */
  public $updatedDevelopmentData = FALSE;

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

  /**
   * Lookup a SF Contact by Email.
   *
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

  /**
   * Add or update a SF Contact.
   *
   * @param array $developmentData
   *   Submission Data.
   *
   * @return mixed
   *   Return SFID of Contact or false
   */
  public function addContact(array $developmentData)
  {

    $contactSFID = $developmentData['contactsfid'] ?? null;

    $contactEmail = $developmentData['contact_email'] ?? NULL;
    $contactName = $developmentData['contact_name'] ?? NULL;
    $contactPhone = $developmentData['contact_phone'] ?? NULL;
    $contactAddress = $developmentData['contact_address'] ?? [];

    $fieldData = [
      // @TODO: Make Config?, Hardcoded the SFID to `DND Contacts`
      'AccountId' => $this->addAccount($developmentData),
      'Email' => $contactEmail,
    ];

    if ($contactPhone) {
      $fieldData['Phone'] = $contactPhone;
    }

    $contactName = explode(' ', $contactName);
    if (isset($contactName[1])) {
      $contactFirstName = $contactName[0];
      $fieldData['FirstName'] = $contactFirstName;
      $contactLastName = $contactName[1];
      $fieldData['LastName'] = $contactLastName;
    } else {
      $contactFirstName = NULL;
      $contactLastName = $contactName[0];
      $fieldData['LastName'] = $contactLastName;
    }

    if (!empty($contactAddress)) {
      $fieldData['MailingStreet'] = $contactAddress['address'];
      $fieldData['MailingCity'] = $contactAddress['city'];
      $fieldData['MailingState'] = $contactAddress['state_province'];
      $fieldData['MailingPostalCode'] = $contactAddress['postal_code'];
    }

    try {
      $contactQuery = new SelectQuery('Contact');
      if ($contactFirstName) {
        $contactQuery->addCondition('FirstName', "'$contactFirstName'");
      }
      $contactQuery->addCondition('LastName', "'$contactLastName'");
      $contactQuery->addCondition('Email', "'$contactEmail'");
      $contactQuery->fields = ['Id', 'Name', 'Email'];

      $existingContact = reset($this->client()->query($contactQuery)->records()) ?? NULL;

    } catch (Exception $exception) {
      \Drupal::logger('bos_metrolist')->error($exception->getMessage());
      return FALSE;
    }

    if ($existingContact) {
      return (string)$existingContact->id();
    } else {
      try {
         return (string) $this->client()->objectUpsert('Contact', 'Id', $contactSFID, $fieldData);
//        return (string)$this->client()->objectCreate('Contact', $fieldData);
      } catch (Exception $exception) {
        \Drupal::logger('bos_metrolist')->error($exception->getMessage());
        return FALSE;
      }
    }
  }

  /**
   * Add or update a SF Account.
   *
   * @param array $developmentData
   *   Submission Data.
   *
   * @return mixed
   *   Return SFID or false
   */
  public function addAccount(array $developmentData)
  {

    try {
      $accountQuery = new SelectQuery('Account');
      $company = $developmentData['contact_company'];
      $accountQuery->addCondition('Name', "'$company'");
      $accountQuery->addCondition('Type', "'Property Manager'");
      $accountQuery->addCondition('Division__c', "'DND'");
      // @TODO: hardcoded to SFID for Account Record Type: "Vendor"
      $accountQuery->addCondition('RecordTypeId', "'012C0000000I0hCIAS'");
      $accountQuery->fields = ['Id', 'Name', 'Type'];

      $existingAccount = reset($this->client()->query($accountQuery)->records()) ?? NULL;

    } catch (Exception $exception) {
      \Drupal::logger('bos_metrolist')->error($exception->getMessage());
      return FALSE;
    }

    if ($existingAccount) {
      // Just return the SFID of the account, no need to update anything....
      return (string)$existingAccount->id();
    } else {
      // Create a new Account in SF.
      $fieldData = [
        'Name' => $developmentData['contact_company'],
        'Business_Legal_Name__c' => $developmentData['contact_company'],
        'Type' => 'Property Manager',
        'Division__c' => 'DND',
        // @TODO: hardcoded to SFID for Account Record Type: "Vendor"
        'RecordTypeId' => '012C0000000I0hCIAS',
      ];

      try {
        return (string)$this->client()->objectCreate('Account', $fieldData);
      } catch (Exception $exception) {
        \Drupal::logger('bos_metrolist')->error($exception->getMessage());
        return FALSE;
      }
    }

  }

  /**
   * Add or update a SF Development.
   *
   * @param string $developmentName
   *   Development Name.
   * @param array $developmentData
   *   Submission Data.
   * @param string $contactId
   *   Contact ID.
   *
   * @return mixed
   *   Return SFID or false
   */
  public function addDevelopment(string $developmentName, array $developmentData, string $contactId)
  {
    $developmentSFID = $developmentData['developmentsfid'] ?? null;

    $fieldData = [
      'Name' => $developmentName,
      'Region__c' => !empty($developmentData['region']) ? $developmentData['region'] : 'Boston',
      'Street_Address__c' => $developmentData['street_address'] ?? '',
      'City__c' => !empty($developmentData['city']) ? $developmentData['city'] : 'Boston',
      'ZIP_Code__c' => $developmentData['zip_code'] ?? '',
      'Wheelchair_Access__c' => empty($developmentData['wheelchair_accessible']) ? FALSE : TRUE,
      'Listing_Contact_Company__c' => $developmentData['contact_company'] ?? NULL,
    ];

    if (isset($contactId)) {
      $fieldData['Listing_Contact__c'] = $contactId;

      // @TODO: change to Man_Comp_Contact__c and set the Account on the Contact and not the Development.
      $fieldData['Management_Company_Contact__c'] = $contactId;
    }

    if (isset($developmentData['neighborhood'])) {
      $fieldData['Neighborhood__c'] = !empty($developmentData['neighborhood']) ? $developmentData['neighborhood'] : NULL;
    }

    if (isset($developmentData['utilities_included'])) {
      $fieldData['Utilities_included__c'] = !empty($developmentData['utilities_included']) ? implode(';', $developmentData['utilities_included']) : NULL;
    }

    if (isset($developmentData['upfront_fees'])) {
      $fieldData['Due_at_signing__c'] = !empty($developmentData['upfront_fees']) ? implode(';', $developmentData['upfront_fees']) : NULL;
    }

    if (isset($developmentData['utilities_included'])) {
      $fieldData['Features__c'] = !empty($developmentData['amenities_features']) ? implode(';', $developmentData['amenities_features']) : NULL;
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
      return (string)$this->client()->objectUpsert('Development__c', 'Id', $developmentSFID, $fieldData);
//      return (string)$this->client()->objectUpsert('Development__c', 'Name', $developmentName, $fieldData);
    } catch (Exception $exception) {
      \Drupal::logger('bos_metrolist')->error($exception->getMessage());
      return FALSE;
    }
  }

  /**
   * Add or update a SF Units.
   *
   * @param array $developmentData
   *   Submission Data.
   * @param string $developmentId
   *   Development ID.
   *
   * @return mixed
   *   Return SFID or false
   */
  public function addUnits(array $developmentData, string $developmentId)
  {

    $units = $developmentData['units'];
//    try {
//
//      $unitsQuery = new SelectQuery('Development_Unit__c');
//      // $unitsQuery->addCondition('Development_new__c', "'a093F000006AFZU'");
//      $unitsQuery->addCondition('Development_new__c', "'$developmentId'");
//      $unitsQuery->fields = ['Id', 'Name'];
//      $unitsResults = $this->client()->query($unitsQuery)->records() ?? NULL;
//
//    } catch (Exception $exception) {
//      \Drupal::logger('bos_metrolist')->error($exception->getMessage());
//      return FALSE;
//    }

    if (!empty($units)) {
      foreach ($units as $unitGroup) {
        if (empty($unitGroup['price'])) {
          continue;
        }
        for ($unitNumber = 1; $unitNumber <= $unitGroup['unit_count']; $unitNumber++) {

          $unitName = $developmentData['street_address'] . ' Unit #' . $unitNumber;

          // @TODO: Change out the values for some of these by updating the options values in the webform configs to match SF.
          $fieldData = [
            'Name' => $unitName,
            'Development_new__c' => $developmentId,
            'Availability_Status__c' => 'Pending',
            'Income_Restricted_new__c' => $developmentData['units_income_restricted'] ?? 'Yes',
            'Availability_Type__c' => $developmentData['available_how'] == 'first_come_first_serve' ? 'First come, first served' : 'Lottery',
            'User_Guide_Type__c' => $developmentData['available_how'] == 'first_come_first_serve' ? 'First come, first served' : 'Lottery',
            'Occupancy_Type__c' => $developmentData['type_of_listing'] == 'rental' ? 'Rent' : 'Own',
            // @TODO: Need to add this to the Listing Form somehow for "% of Income"
            'Rent_Type__c' => 'Fixed $',
            'Income_Eligibility_AMI_Threshold__c' => isset($unitGroup['ami']) ? $unitGroup['ami'] : 'N/A',
            'Number_of_Bedrooms__c' => isset($unitGroup['bedrooms']) ? (double)$unitGroup['bedrooms'] : 0.0,
            'Rent_or_Sale_Price__c' => isset($unitGroup['price']) ? (double)filter_var($unitGroup['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0.0,
            'ADA_V__c' => empty($unitGroup['ada_v']) ? FALSE : TRUE,
            'ADA_H__c' => empty($unitGroup['ada_h']) ? FALSE : TRUE,
            'ADA_M__c' => empty($unitGroup['ada_m']) ? FALSE : TRUE,
            'Waitlist_Open__c' => $developmentData['waitlist_open'] == 'No' || empty($developmentData['waitlist_open']) ? FALSE : TRUE,
          ];

          if (isset($unitGroup['bathrooms'])) {
            $fieldData['Number_of_Bathrooms__c'] = isset($unitGroup['bathrooms']) ? (double)$unitGroup['bathrooms'] : 0.0;
          }

          if (isset($unitGroup['minimum_income_threshold'])) {
            $fieldData['Minimum_Income_Threshold__c'] = !empty($unitGroup['minimum_income_threshold']) ? (double)filter_var($unitGroup['minimum_income_threshold'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0.0;
          }

          if (isset($developmentData['posted_to_metrolist_date'])) {
            $fieldData['Requested_Publish_Date__c'] = $developmentData['posted_to_metrolist_date'];
          }

          if (isset($developmentData['application_deadline_datetime'])) {
            $fieldData['Lottery_Application_Deadline__c'] = $developmentData['application_deadline_datetime'];
          }

          if (isset($developmentData['website_link'])) {
            $fieldData['Lottery_Application_Website__c'] = $developmentData['website_link'] ?? NULL;
          }

          try {
//            $this->client()->objectUpsert('Development_Unit__c', 'Name', $unitName, $fieldData);
            $this->client()->objectCreate('Development_Unit__c', $fieldData);
          } catch (Exception $exception) {
            \Drupal::logger('bos_metrolist')->error($exception->getMessage());
            return FALSE;
          }

        }

      }
    }
  }


  public function updateUnits(array $developmentData, string $developmentId)
  {

    $currentUnits = $developmentData['current_units'];
//    try {
//
//      $unitsQuery = new SelectQuery('Development_Unit__c');
//      // $unitsQuery->addCondition('Development_new__c', "'a093F000006AFZU'");
//      $unitsQuery->addCondition('Development_new__c', "'$developmentId'");
//      $unitsQuery->fields = ['Id', 'Name'];
//      $unitsResults = $this->client()->query($unitsQuery)->records() ?? NULL;
//
//    } catch (Exception $exception) {
//      \Drupal::logger('bos_metrolist')->error($exception->getMessage());
//      return FALSE;
//    }

    if (!empty($currentUnits)) {
    foreach ($currentUnits as $unit) {

      if (empty($unit['relist_unit'])) {
        continue;
      }

//      for ($unitNumber = 1; $unitNumber <= $unitGroup['unit_count']; $unitNumber++) {

//        $unitName = $developmentData['street_address'] . ' Unit #' . $unitNumber;

        // @TODO: Change out the values for some of these by updating the options values in the webform configs to match SF.
        $fieldData = [
//          'Name' => $unitName,
//          'Development_new__c' => $developmentId,
          'Availability_Status__c' => 'Pending',
//          'Income_Restricted_new__c' => $developmentData['units_income_restricted'] ?? 'Yes',
          'Availability_Type__c' => 'First come, first served',
          'User_Guide_Type__c' => 'First come, first served',
//          'Occupancy_Type__c' => $developmentData['type_of_listing'] == 'rental' ? 'Rent' : 'Own',
          // @TODO: Need to add this to the Listing Form somehow for "% of Income"
//          'Rent_Type__c' => 'Fixed $',
//          'Income_Eligibility_AMI_Threshold__c' => isset($unitGroup['ami']) ? $unitGroup['ami'] . '% AMI' : 'N/A',
//          'Number_of_Bedrooms__c' => isset($unitGroup['bedrooms']) ? (double)$unitGroup['bedrooms'] : 0.0,
          'Rent_or_Sale_Price__c' => isset($unit['price']) ? (double)filter_var($unit['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0.0,
//          'ADA_V__c' => empty($unitGroup['ada_v']) ? FALSE : TRUE,
//          'ADA_H__c' => empty($unitGroup['ada_h']) ? FALSE : TRUE,
//          'ADA_M__c' => empty($unitGroup['ada_m']) ? FALSE : TRUE,
          'Waitlist_Open__c' => $developmentData['waitlist_open'] == 'No' || empty($developmentData['waitlist_open']) ? FALSE : TRUE,
        ];

        if (!empty($unit['minimum_income_threshold'])) {
          $fieldData['Minimum_Income_Threshold__c'] = !empty($unit['minimum_income_threshold']) ? (double)filter_var($unit['minimum_income_threshold'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0.0;
        }

        if (!empty($developmentData['posted_to_metrolist_date'])) {
          $fieldData['Requested_Publish_Date__c'] = $developmentData['posted_to_metrolist_date'];
        }

        if (!empty($developmentData['application_deadline_datetime'])) {
          $fieldData['Lottery_Application_Deadline__c'] = $developmentData['application_deadline_datetime'];
        }

        if (!empty($developmentData['website_link'])) {
          $fieldData['Lottery_Application_Website__c'] = $developmentData['website_link'] ?? NULL;
        }

        try {
          $this->client()->objectUpdate('Development_Unit__c', $unit['sfid'], $fieldData);
//          $this->client()->objectUpsert('Development_Unit__c', 'Name', $unitName, $fieldData);
        } catch (Exception $exception) {
          \Drupal::logger('bos_metrolist')->error($exception->getMessage());
          return FALSE;
        }

//      }

    }
    }
  }


  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE)
  {

    $fieldData = $webform_submission->getData();

    if ($webform_submission->isCompleted()) {
      $contactId = $this->addContact($fieldData) ?? null;

      if ($contactId) {
        $developmentId = $this->addDevelopment($fieldData['property_name'], $fieldData, $contactId) ?? null;

        if ($developmentId) {
          if ($fieldData['update_unit_information']) {
            $this->updateUnits($fieldData, $developmentId);
          }
          $this->addUnits($fieldData, $developmentId);
        }
      }
    }

    // TODO: Change the autogenerated stub.
    parent::postSave($webform_submission, $update);
  }

  /**
   *
   */
  public function postLoad(WebformSubmissionInterface $webform_submission)
  {
    $test1 = 1;
    // TODO: Change the autogenerated stub.
    parent::postLoad($webform_submission);
    $test2 = 2;
  }

  /**
   *
   */
  public function preSave(WebformSubmissionInterface $webform_submission)
  {

    if ($contactSFID = $webform_submission->getElementData('select_contact')) {
      if ($contactSFID != 'new' && (empty($webform_submission->getElementData('contactsfid')) || $contactSFID != $webform_submission->getElementData('contactsfid'))) {
        // Get Contact Object.
        $contactData = $this->client()->objectRead('Contact', $contactSFID);
        // Set field value.

        $accountData = $contactData->hasField('AccountId') ? $this->client()->objectRead('Account', $contactData->field('AccountId')) : null;

        $fields = [
          'contact_name' => 'Name',
          'contact_address' => 'MailingAddress',
          'contact_phone' => 'Phone',
          'contact_company' => 'Account'
        ];

        foreach ($fields as $webform_field => $sf_field) {
//          if (empty($webform_submission->getElementData($webform_field))) {

          if ($webform_field == 'contact_address') {
            $sf_field = [
              'address' => $contactData->field('MailingStreet'),
              'city' => $contactData->field('MailingCity'),
              'state_province' => $contactData->field('MailingState'),
              'postal_code' => $contactData->field('MailingPostalCode'),
              'address_2' => "",
              'country' => "",
            ];

            $webform_submission->setElementData($webform_field, $sf_field);

          } elseif ($webform_field == 'contact_company' && $accountData) {

            $sf_field = $accountData->field('Name');
            $webform_submission->setElementData($webform_field, $sf_field);
          } else {

            $webform_submission->setElementData($webform_field, $contactData->field($sf_field));
          }
//          }
        }

        $webform_submission->setElementData('contactsfid', $contactSFID);
      }
    }

    if ($developmentSFID = $webform_submission->getElementData('select_development')) {
      if ($developmentSFID != 'new' && (empty($webform_submission->getElementData('developmentsfid')) || $developmentSFID != $webform_submission->getElementData('developmentsfid'))) {
        // If ($developmentSFID != 'new' && empty($webform_submission->getElementData('developmentsfid'))) {.

        $developmentData = $this->client()->objectRead('Development__c', $developmentSFID);

        $fields = [
          'property_name' => 'Name',
          'city' => 'City__c',
          'region' => 'Region__c',
          'neighborhood' => 'Neighborhood__c',
          // 'building_in_boston' => 'Name',
          'street_address' => 'Street_Address__c',
          'zip_code' => 'ZIP_Code__c',
          'upfront_fees' => 'Due_at_signing__c',
          'amenities_features' => 'Features__c',
          'utilities_included' => 'Utilities_included__c',
          'public_contact_email' => 'Public_Contact_Email__c',
          'public_contact_name' => 'Public_Contact_Name__c',
          'property_management_company' => 'Listing_Contact_Company__c',
          'public_contact_phone' => 'Public_Contact_Phone__c',
          'public_contact_address' => 'Public_Contact_Address__c',
          'wheelchair_accessible' => 'Wheelchair_Access__c'
        ];

        foreach ($fields as $webform_field => $sf_field) {
//          if (empty($webform_submission->getElementData($webform_field))) {

          if ($webform_field == 'upfront_fees' || $webform_field == 'amenities_features' || $webform_field == 'utilities_included') {
            $sf_field = explode(';', $developmentData->field($sf_field));
            $webform_submission->setElementData($webform_field, $sf_field);

          } elseif ($webform_field == 'public_contact_address') {

            //@TODO: Re-factor this!!!
            $sf_public_address = explode("\r\n", $developmentData->field('Public_Contact_Address__c'));
            $sf_public_street = $sf_public_address[0];
            $sf_public_address = explode(', ', $sf_public_address[1]);
            $sf_public_city = $sf_public_address[0];
            $sf_public_address = explode(' ', $sf_public_address[1]);
            $sf_public_state = $sf_public_address[0];
            $sf_public_zipcode = $sf_public_address[1];

            $sf_field = [
              'address' => $sf_public_street,
              'city' => $sf_public_city,
              'state_province' => $sf_public_state,
              'postal_code' => $sf_public_zipcode,
              'address_2' => "",
              'country' => "",
            ];
            $webform_submission->setElementData($webform_field, $sf_field);

          } else {

            $webform_submission->setElementData($webform_field, $developmentData->field($sf_field));
          }
//          }
        }


        $this->updatedDevelopmentData = TRUE;
        $webform_submission->setElementData('developmentsfid', $developmentSFID);
      }
    }

    if (isset($developmentSFID) && $developmentSFID == $webform_submission->getElementData('developmentsfid') && $webform_submission->getElementData('update_unit_information')) {


      $salesForce = new MetroListSalesForceConnection();

      $currentUnits = $salesForce->getUnitsByDevelopmentSID($developmentSFID);


      if ($currentUnits && empty($webform_submission->getElementData('current_units')[0]['sfid']) || ($currentUnits && $this->updatedDevelopmentData)) {
        $this->updatedDevelopmentData = FALSE;
        $webformCurrentUnits = [];
        foreach ($currentUnits as $unitSFID => $currentUnit) {
//          $adaUnit = ($currentUnit->field('ADA_H__c') || $currentUnit->field('ADA_V__c') || $currentUnit->field('ADA_M__c')) ? "&#128065;" : '-';

          $adaUnit = [];

          if ($currentUnit->field('ADA_H__c')) {
            $adaUnit[] = '✓ Hearing';
          }
          if ($currentUnit->field('ADA_V__c')) {
            $adaUnit[] = '✓ Visual';
          }
          if ($currentUnit->field('ADA_M__c')) {
            $adaUnit[] = '✓ Mobility';
          }

          $adaUnit = !empty($adaUnit) ? implode("\r", $adaUnit) : "";

          $webformCurrentUnits[] = [
            'relist_unit' => 0,
            'ada' => $adaUnit,
            'ami' => $currentUnit->field('Income_Eligibility_AMI_Threshold__c'),
            'bedrooms' => $currentUnit->field('Number_of_Bedrooms__c'),
            'price' => $currentUnit->field('Rent_or_Sale_Price__c'),
            'minimum_income_threshold' => $currentUnit->field('Minimum_Income_Threshold__c'),
            'status' => $currentUnit->field('Availability_Status__c'),
            'sfid' => $currentUnit->field('Id'),
          ];

        }
        $webform_submission->setElementData('current_units', $webformCurrentUnits);
//        $webform_submission->save();
      }


    }


    // TODO: Change the autogenerated stub.
    parent::preSave($webform_submission);
  }

}
