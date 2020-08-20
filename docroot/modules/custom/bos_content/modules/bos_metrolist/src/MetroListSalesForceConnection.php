<?php

namespace Drupal\bos_metrolist;

use Drupal\salesforce\Exception;
use Drupal\salesforce\SelectQuery;
use Drupal\webform\Entity\WebformSubmission;

/**
 *
 */
class MetroListSalesForceConnection {

  // Public function __construct()
  //  {
  //
  //  }
  /**
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  private $webformSubmission = NULL;
  private $contactEmail = NULL;

  /**
   * {@inheritdoc}
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
   * @param null $sid
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  public function loadWebformSubmission($sid = NULL) {
    if ($this->webformSubmission && is_null($sid)) {
      return $this->webformSubmission;
    }

    $sid = $sid ?? reset($_SESSION['webform_submissions']) ?? NULL;
    $this->webformSubmission = WebformSubmission::load($sid) ?? NULL;

    return $this;
  }

  /**
   *
   */
  public function webformSubmission() {
    return $this->webformSubmission;
  }

  /**
   *
   */
  public function getContactEmail() {
    if ($this->webformSubmission()) {
      $this->contactEmail = $this->webformSubmission()->getElementData('contact_email') ?? NULL;
    }
    return $this->contactEmail;
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
   *
   */
  public function getContactsByEmail(string $email) {

    try {
      $contactQuery = new SelectQuery('Contact');
      // If ($contactFirstName) {
      //        $contactQuery->addCondition('FirstName', "'$contactFirstName'");
      //      }
      //      $contactQuery->addCondition('LastName', "'$contactLastName'");.
      $contactQuery->addCondition('Email', "'" . urlencode($email) . "'");
      $contactQuery->fields = ['Id', 'Name', 'Email'];

      return $this->client()->query($contactQuery)->records() ?? NULL;
    }
    catch (Exception $exception) {
      \Drupal::logger('bos_metrolist')->error($exception->getMessage());
      return FALSE;
    }

  }

  /**
   *
   */
  public function getContactOptionsByEmail(string $email = NULL) {
    if (empty($email)) {
      return;
    }

    $options = [];

    foreach ($this->getContactsByEmail($email) as $contact) {
      if ($contact->hasField('Id') && $contact->hasField('Name')) {
        $options[$contact->field('Id')] = $contact->field('Name');
      }
    }

    return $options;
  }

  /**
   *
   */
  public function getDevelopmentsByContactSID(string $contactSID) {

    try {
      $developmentQuery = new SelectQuery('Development__c');
      $developmentQuery->addCondition('Listing_Contact__c', "'$contactSID'");
      $developmentQuery->fields = ['Id', 'Name', 'Street_Address__c', 'City__c'];

      return $this->client()->query($developmentQuery)->records() ?? NULL;
    }
    catch (Exception $exception) {
      \Drupal::logger('bos_metrolist')->error($exception->getMessage());
      return FALSE;
    }

  }

  /**
   *
   */
  public function getDevelopmentOptionsByContactSID(string $contactSID = NULL) {
    if (empty($contactSID) || $contactSID == 'new') {
      return;
    }

    $options = [];

    foreach ($this->getDevelopmentsByContactSID($contactSID) as $development) {
      if ($development->hasField('Id') && $development->hasField('Name')) {
        $options[$development->field('Id')] = $development->field('Name') . ' (' . $development->field('Street_Address__c') . ', ' . $development->field('City__c') . ')';
      }
    }

    return $options;
  }


  /**
   *
   */
  public function getUnitsByDevelopmentSID(string $developmentSFID) {

    try {
      $developmentQuery = new SelectQuery('Development_Unit__c');
      $developmentQuery->addCondition('Development_new__c', "'$developmentSFID'");
      $developmentQuery->fields = [
        'Id',
        'Name',
        'Availability_Status__c',
        'Income_Eligibility_AMI_Threshold__c',
        'Number_of_Bedrooms__c',
        'Rent_or_Sale_Price__c',
        'Minimum_Income_Threshold__c',
        'ADA_H__c',
        'ADA_V__c',
        'ADA_M__c',
        ];

      return $this->client()->query($developmentQuery)->records() ?? NULL;
    }
    catch (Exception $exception) {
      \Drupal::logger('bos_metrolist')->error($exception->getMessage());
      return FALSE;
    }

  }

  public function getPickListValues(string $sf_object, string $sf_field, array $exclude = []) {
    $values = [];
    $picklistData = $this->client()->objectDescribe($sf_object)->getField($sf_field)['picklistValues'] ?? null;

    if ($picklistData) {
      foreach ($picklistData as $option) {
        if ($option['active'] && !in_array($option['value'], $exclude)) {
          $values[$option['value']] = $option['label'];
        }
      }
    }

    return $values;
  }

}
