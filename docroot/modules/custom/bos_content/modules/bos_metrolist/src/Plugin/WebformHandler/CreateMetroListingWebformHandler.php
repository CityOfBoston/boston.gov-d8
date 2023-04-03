<?php

namespace Drupal\bos_metrolist\Plugin\WebformHandler;

use Drupal\salesforce\Exception;
use Drupal\salesforce\Rest\RestException;
use Drupal\salesforce\SelectQuery;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\bos_metrolist\MetroListSalesForceConnection;
use Drupal\file\Entity\File;

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
class CreateMetroListingWebformHandler extends WebformHandlerBase {
  /**
   * Flag for Development Updated.
   *
   * @var bool
   */
  public $updatedDevelopmentData = FALSE;

  /**
   * @return \Drupal\salesforce\Rest\RestClient
   */
  public function client() {
    return \Drupal::service('salesforce.client');
  }

  /**
   * {@inheritdoc}
   */
  public function authMan() {
    return \Drupal::service('plugin.manager.salesforce.auth_providers');
  }

  /**
   * {@inheritdoc}
   */
  public function getSalesforceUrl() {
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
  public function getContactByEmail($email = '') {

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
  public function addContact(array $developmentData) {

    $contactSFID = $developmentData['contactsfid'] ?? NULL;

    $contactEmail = $developmentData['contact_email'] ?? NULL;
    $contactName = $developmentData['contact_name'] ?? NULL;
    $contactPhone = $developmentData['contact_phone'] ?? NULL;
    $contactAddress = $developmentData['contact_address'] ?? [];

    $fieldData = [
      // @TODO: Make Config?, Hardcoded the SFID to `DND Contacts`
      'AccountId' => $this->addAccount($developmentData),
      'Email' => $contactEmail,
    ];

    if (empty($fieldData['AccountId'])) {
      // Effectively bubble up the addAccount error.
      return FALSE;
    }

    if ($contactPhone) {
      $fieldData['Phone'] = $contactPhone;
    }

    $contactName = explode(' ', $contactName);
    if (isset($contactName[1])) {
      $fieldData['FirstName'] = $contactName[0];
      $fieldData['LastName'] = $contactName[1];
    }
    else {
      $fieldData['FirstName'] = '';
      $fieldData['LastName'] = $contactName[0];
    }

    if (!empty($contactAddress)) {
      $fieldData['MailingStreet'] = $contactAddress['address'];
      $fieldData['MailingCity'] = $contactAddress['city'];
      $fieldData['MailingState'] = $contactAddress['state_province'];
      $fieldData['MailingPostalCode'] = $contactAddress['postal_code'];
    }

    return $this->updateSalesforce('Contact', $fieldData, "Id", $contactSFID ?? NULL);

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
  public function addAccount(array $developmentData) {

    try {
      $accountQuery = new SelectQuery('Account');
      $company = $developmentData['contact_company'];
      // DU ticket #2494 DIG-79
      // Escape apostrophes b/c of the way the string is built on the next line.
      $company = str_replace("'", "\'", $company);
      $accountQuery->addCondition('Name', "'$company'");
      $accountQuery->addCondition('Type', "'Property Manager'");
      $accountQuery->addCondition('Division__c', "'DND'");
      // @TODO: hardcoded to SFID for Account Record Type: "Vendor"
      $accountQuery->addCondition('RecordTypeId', "'012C0000000I0hCIAS'");
      $accountQuery->fields = ['Id', 'Name', 'Type'];

      $existingAccount = $this->client()->query($accountQuery)->records() ?? NULL;
      $existingAccount = reset($existingAccount) ?? NULL;

    }
    catch (Exception $exception) {
      \Drupal::logger('bos_metrolist')->error($exception->getMessage());
      return FALSE;
    }

    if ($existingAccount) {
      // Just return the SFID of the account, no need to update anything....
      return (string) $existingAccount->id();
    }
    else {
      // Create a new Account in SF.
      $fieldData = [
        'Name' => $developmentData['contact_company'],
        'Business_Legal_Name__c' => $developmentData['contact_company'],
        'Type' => 'Property Manager',
        'Division__c' => 'DND',
        // @TODO: hardcoded to SFID for Account Record Type: "Vendor"
        'RecordTypeId' => '012C0000000I0hCIAS',
      ];
      return $this->updateSalesforce('Account', $fieldData);
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
  public function addDevelopment(string $developmentName, array $developmentData, string $contactId) {
    $developmentSFID = $developmentData['developmentsfid'] ?? NULL;

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
    }
    else {
      if (!empty($developmentData['contact_address'])) {
        $addr = $developmentData['contact_address'];
        $fieldData['Public_Contact_Address__c'] = $addr['address'] . "\r\n" . $addr['city'] . ", " . $addr['state_province'] . " " . $addr['postal_code'];
      }
      $fieldData['Public_Contact_Email__c'] = $developmentData['contact_email'];
      $fieldData['Public_Contact_Name__c'] = $developmentData['contact_name'];
      $fieldData['Public_Contact_Phone__c'] = $developmentData['contact_phone'];
    }

    return $this->updateSalesforce('Development__c', $fieldData, 'Id', $developmentSFID ?? NULL);

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
  public function addUnits(array $developmentData, string $developmentId) {

    $units = $developmentData['units'];

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
            'Suggested_Removal_Date__c' => $developmentData['remove_posting_date'] ?? NULL,
            'Income_Restricted_new__c' => $developmentData['units_income_restricted'] ?? 'Yes',
            'Availability_Type__c' => $developmentData['available_how'] == 'first_come_first_serve' ? 'First come, first served' : 'Lottery',
            'User_Guide_Type__c' => $developmentData['available_how'] == 'first_come_first_serve' ? 'First come, first served' : 'Lottery',
            'Occupancy_Type__c' => $developmentData['type_of_listing'] == 'rental' ? 'Rent' : 'Own',
            // @TODO: Need to add this to the Listing Form somehow for "% of Income"
            'Rent_Type__c' => 'Fixed $',
            'Income_Eligibility_AMI_Threshold__c' => isset($unitGroup['ami']) ? $unitGroup['ami'] : 'N/A',
            'Number_of_Bedrooms__c' => isset($unitGroup['bedrooms']) ? (double) $unitGroup['bedrooms'] : 0.0,
            'Rent_or_Sale_Price__c' => isset($unitGroup['price']) ? (double) filter_var($unitGroup['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0.0,
            'ADA_V__c' => empty($unitGroup['ada_v']) ? FALSE : TRUE,
            'ADA_H__c' => empty($unitGroup['ada_h']) ? FALSE : TRUE,
            'ADA_M__c' => empty($unitGroup['ada_m']) ? FALSE : TRUE,
            'Waitlist_Open__c' => $developmentData['waitlist_open'] == 'No' || empty($developmentData['waitlist_open']) ? FALSE : TRUE,
          ];

          if (isset($unitGroup['bathrooms'])) {
            $fieldData['Number_of_Bathrooms__c'] = isset($unitGroup['bathrooms']) ? (double) $unitGroup['bathrooms'] : 0.0;
          }

          if (isset($unitGroup['minimum_income_threshold'])) {
            $fieldData['Minimum_Income_Threshold__c'] = !empty($unitGroup['minimum_income_threshold']) ? (double) filter_var($unitGroup['minimum_income_threshold'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0.0;
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

          if (isset($developmentData['pdf_upload']) && $developmentData['direct_visitors'] == 'pdf') {
            $fieldData['Lottery_Application_Website__c'] = \Drupal::service('file_url_generator')->generateAbsoluteString(File::load($developmentData['pdf_upload'])->getFileUri()) ?? NULL;
          }

          if ($this->updateSalesforce('Development_Unit__c', $fieldData) === FALSE) {
            return FALSE;
          }

        }

      }
    }
    return TRUE;
  }

  /**
   * Update a SF Units.
   *
   * @param array $developmentData
   *   Submission Data.
   * @param string $developmentId
   *   Development ID.
   *
   * @return mixed
   *   Return SFID or false
   */
  public function updateUnits(array $developmentData, string $developmentId) {

    $currentUnits = $developmentData['current_units'];

    if (!empty($currentUnits)) {
      foreach ($currentUnits as $unit) {

        if (empty($unit['relist_unit'])) {
          continue;
        }

        // @TODO: Change out the values for some of these by updating the options values in the webform configs to match SF.
        $fieldData = [
          'Availability_Status__c' => 'Pending',
          'Availability_Type__c' => 'First come, first served',
          'Suggested_Removal_Date__c' => $developmentData['remove_posting_date'] ?? NULL,
          'User_Guide_Type__c' => 'First come, first served',
          'Rent_or_Sale_Price__c' => isset($unit['price']) ? (double) filter_var($unit['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0.0,
          'Waitlist_Open__c' => $developmentData['waitlist_open'] == 'No' || empty($developmentData['waitlist_open']) ? FALSE : TRUE,
        ];

        if (!empty($unit['minimum_income_threshold'])) {
          $fieldData['Minimum_Income_Threshold__c'] = !empty($unit['minimum_income_threshold']) ? (double) filter_var($unit['minimum_income_threshold'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0.0;
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

        if ($this->updateSalesforce('Development_Unit__c', $fieldData, NULL, $unit['sfid']) === FALSE) {
          return FALSE;
        }

      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {

    // TODO: Change the autogenerated stub.
    parent::postSave($webform_submission, $update);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {

    /*
     * Populate the form with the contact info from SF.
     * If an existing contact is selected in the contact dropdown, then the
     * contactsfid field is set with the SF ID for that contact. For new
     * contacts the field is blank.
     */
    if ($contactSFID = $webform_submission->getElementData('select_contact')) {
      // If the contact selected is not new and: either the contactsfid field is
      // empty or the contactsfid does not equal the contact ID already selected,
      // then fetch the contact from SF and populate the information into the
      // form.
      if ($contactSFID != 'new'
        && (empty($webform_submission->getElementData('contactsfid'))
          || $contactSFID != $webform_submission->getElementData('contactsfid')
          || empty($webform_submission->getElementData('contact_name', '')))) {

        $contactData = $this->client()->objectRead('Contact', $contactSFID);
        $accountData = $contactData->hasField('AccountId') ? $this->client()
          ->objectRead('Account', $contactData->field('AccountId')) : NULL;

        $fields = [
          'contact_name' => 'Name',
          'contact_address' => 'MailingAddress',
          'contact_phone' => 'Phone',
          'contact_company' => 'Account'
        ];

        foreach ($fields as $webform_field => $sf_field) {
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

          }
          elseif ($webform_field == 'contact_company' && $accountData) {
            $sf_field = $accountData->field('Name');
            $webform_submission->setElementData($webform_field, $sf_field);
          }
          else {
            $webform_submission->setElementData($webform_field, $contactData->field($sf_field));
          }
        }

        if (empty($webform_submission->getElementData("contact_email"))) {
          $webform_submission->setElementData("contact_email", $contactData->field("Email"));
        }

        $webform_submission->setElementData('contactsfid', $contactSFID);
      }
    }

    /*
     * Populate the form with the developments info from SF.
     * If an existing development (Building) is selected in the Building
     * dropdown, then the developmentsfid field is set with the SF ID for that
     * development. For new buildings (developments) the field is blank.
     */
    if ($developmentSFID = $webform_submission->getElementData('select_development')) {
      // If the development selected is not new and: either the developmentsfid
      // field is empty or the developmentsfid does not equal the development ID
      // already selected, then fetch the development from SF and populate the
      // information into the form.
      if ($developmentSFID != 'new'
        && (empty($webform_submission->getElementData('developmentsfid'))
          || $developmentSFID != $webform_submission->getElementData('developmentsfid')
          || empty($webform_submission->getElementData('property_name', '')))) {

        $developmentData = $this->client()
          ->objectRead('Development__c', $developmentSFID);

        $fields = [
          'property_name' => 'Name',
          'city' => 'City__c',
          'region' => 'Region__c',
          'neighborhood' => 'Neighborhood__c',
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
          // If (empty($webform_submission->getElementData($webform_field))) {.

          if ($webform_field == 'upfront_fees' || $webform_field == 'amenities_features' || $webform_field == 'utilities_included') {
            $sf_field = explode(';', $developmentData->field($sf_field));
            $webform_submission->setElementData($webform_field, $sf_field);

          }
          elseif ($webform_field == 'public_contact_address') {

            // @TODO: Re-factor this!!!
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

          }
          else {

            $webform_submission->setElementData($webform_field, $developmentData->field($sf_field));
          }
          // }
        }

        $this->updatedDevelopmentData = TRUE;
        $webform_submission->setElementData('developmentsfid', $developmentSFID);
      }
    }
    // Then also load the units for the development found into the form.
    if (isset($developmentSFID)
      && $developmentSFID == $webform_submission->getElementData('developmentsfid')
      && $webform_submission->getElementData('update_unit_information')) {

      $salesForce = new MetroListSalesForceConnection();

      $currentUnits = $salesForce->getUnitsByDevelopmentSid($developmentSFID);

      if ($currentUnits
        && empty($webform_submission->getElementData('current_units')[0]['sfid'])
        || ($currentUnits && $this->updatedDevelopmentData)) {
        $this->updatedDevelopmentData = FALSE;
        $webformCurrentUnits = [];
        foreach ($currentUnits as $unitSFID => $currentUnit) {
          // $adaUnit = ($currentUnit->field('ADA_H__c') || $currentUnit->field('ADA_V__c') || $currentUnit->field('ADA_M__c')) ? "&#128065;" : '-';

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
        // $webform_submission->save();
      }

    }

    if ($webform_submission->isCompleted()) {
      // If the form is complete, then update salesforce and save the sfid's
      // if we don't already have them.
      // DU Note, this block was previously in postSave() but that did not give
      // the opportunity to save sfids for new developments and contacts.
      // DU Note, I am re-purposing the sfid fields, hopefully there is not any
      // code which uses them to determine upsert/insert activities.
      $fieldData = $webform_submission->getData();
      $contactId = $this->addContact($fieldData) ?? NULL;
      // Reset this b/c it gets saved with the form.
      $webform_submission->setElementData('formerrors', 0);

      if (empty($fieldData["contact_email"])) {
        // This should not happen.
        if (!empty($contactId) && empty($contactData)) {
          $contactData = $this->client()->objectRead('Contact', $contactId);
        }
        if (!empty($contactData)) {
          $webform_submission->setElementData("contact_email", $contactData->field("Email"));
          $fieldData['contact_email'] = $contactData->field("Email");
        }
        else {
          // This is only an issue at this point b/c the confirmation email
          // will not be sent to the contact.  Also sf may be updated with
          // the empty email address.
        }
      }

      if ($contactId === FALSE) {
        // An error occurred.
        // Set this flag so that confirmation emails are not sent out.
        $webform_submission->setElementData('formerrors', '1');
      }
      else {
        if (empty($fieldData['contactsfid'])) {
          $webform_submission->setElementData('contactsfid', $contactId);
        }
        $developmentId = $this->addDevelopment($fieldData['property_name'], $fieldData, $contactId) ?? NULL;

        if ($developmentId === FALSE) {
          // An error occurred.
          // Set this flag so that confirmation emails are not sent out.
          $webform_submission->setElementData('formerrors', '1');
        }
        else {
          if (empty($fieldData['developmentsfid'])) {
            $webform_submission->setElementData("developmentsfid", $developmentId);
          }
          if ($fieldData['update_unit_information']) {
            if ($this->updateUnits($fieldData, $developmentId) === FALSE) {
              // An error occurred.
              // Set this flag so that confirmation emails are not sent out.
              $webform_submission->setElementData('formerrors', '1');
            }
          }
          if ($this->addUnits($fieldData, $developmentId) === FALSE) {
            // An error occurred.
            // Set this flag so that confirmation emails are not sent out.
            $webform_submission->setElementData('formerrors', '1');
          }
        }
      }
    }

    parent::preSave($webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $a = parent::getSummary();
    unset($a["#theme"]);
    $a["#markup"] = "Custom WebformHandler defined in module bos_metrolist";
    return $a;
  }

  /**
   * Helper function to interract with salesforce client.
   *
   * @param string $name The sf object name to use
   * @param array $params The data to insert/update into the sf object
   * @param string $key The unique key for the sf object for upsert (if missing then create will be used)
   * @param string $value The unique key value for upsert
   *
   * @return false|string The response from Salesforce.
   */
  private function updateSalesforce(string $name, array $params, $key = NULL, $value = NULL) {

    try {
      if (!empty($key) && !empty($value)) {
        $result = (string) $this->client()->objectUpsert($name, $key, $value, $params);
      }
      elseif (empty($key) && !empty($value)) {
        $result = (string) $this->client()->objectUpdate($name, $value, $params);
      }
      else {
        $result = (string) $this->client()->objectCreate($name, $params);
      }
      return $result;
    }
    catch (RestException | Exception $exception) {
      \Drupal::logger('bos_metrolist')->error($exception->getMessage());
      $this->messenger()->addError("Error saving submission");
      return FALSE;
    }

  }
}
