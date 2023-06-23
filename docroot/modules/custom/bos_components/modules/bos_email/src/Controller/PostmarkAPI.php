<?php

namespace Drupal\bos_email\Controller;

use bos_core\Boston;
use Drupal\bos_email\CobEmail;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Postmark class for API.
 */
class PostmarkAPI extends ControllerBase {

  const MESSAGE_SENT = 'Message sent.';

  const MESSAGE_QUEUED = 'Message queued.';

  const POSTMARK_DEFAULT_ENDPOINT = 'https://api.postmarkapp.com/email';
  const POSTMARK_TEMPLATE_ENDPOINT = "https://api.postmarkapp.com/email/withTemplate";

  const AUTORESPONDER_SERVERNAME = 'autoresponder';

  /**
   * Current request object for this class.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  public $request;

  /**
   * Server hosted / mapped to Postmark.
   *
   * @var string
   */
  public $server;

  /**
   * @var boolean
   */
  public $debug;

  private string $error = "";

  /** @var \Drupal\bos_email\EmailTemplateInterface */
  private $template_class;

  private string $honeypot;

  /**
   * Public construct for Request.
   */
  public function __construct(RequestStack $request) {
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('request_stack')
    );
  }

  /**
   * Check / set valid session token.
   *
   */
  public function token(string $operation) {
    $data = $this->request->getCurrentRequest()->get('data');
    $token = new TokenOps();

    if ($operation == "create") {
      $response_token = $token->tokenCreate();

    }
    elseif ($operation == "remove") {
      $response_token = $token->tokenRemove($data);

    }
    else {
      $response_token = $token->tokenGet($data);

    }

    $response = new CacheableJsonResponse($response_token);
    return $response;
  }

  /**
   * Load an email into the queue for later dispatch.
   *
   * @param array $data
   *   The array containing the email POST data.
   */
  public function addQueueItem(array $data) {
    $queue_name = 'email_contactform';
    $queue = \Drupal::queue($queue_name);
    $queue_item_id = $queue->createItem($data);

    return $queue_item_id;
  }

  /**
   * Check the authentication key sent in the header is valid.
   *
   * @return bool
   */
  private function authenticate() {
    $postmark_auth = new PostmarkOps();
    return $postmark_auth->checkAuth($this->request->getCurrentRequest()->headers->get("authorization"));
  }

  /**
   * Send email via Postmark API.
   *
   * @param array $emailFields
   *   The array containing Postmark API needed fieds.
   */
  private function formatEmail(array &$emailFields) {

    // Create a nicer sender address if possible.
    $emailFields["modified_from_address"] = $emailFields["from_address"];
    if (isset($emailFields["sender"])) {
      $emailFields["modified_from_address"] = "{$emailFields["sender"]}<{$emailFields["from_address"]}>";
    }

    $emailFields["postmark_data"] = new CobEmail([
      "server" => $this->server
    ]);

    if (isset($this->template_class)) {
      // This allows us to inject custom templates to reformat the email.
      $this->template_class::templateFormatEmail($emailFields);
    }

    else {
      // No class created to template the response.
      // Create a default message for sending.
      $cobdata = $emailFields["postmark_data"];
      $cobdata->setField("postmark_endpoint", $this::POSTMARK_DEFAULT_ENDPOINT);
      $cobdata->setField("To", $emailFields["to_address"]);
      $cobdata->setField("From", $emailFields["modified_from_address"]);

      if (isset($emailFields["template_id"])) {
        $cobdata->setField("postmark_endpoint", "https://api.postmarkapp.com/email/withTemplate");
        $cobdata->setField("TemplateID", $emailFields["template_id"]);
        $cobdata->setField("TemplateModel", [
          "Subject" => $emailFields["subject"],
          "TextBody" => $emailFields["message"],
          "ReplyTo" => $emailFields["from_address"]
        ]);
        $cobdata->delField("ReplyTo");
        $cobdata->delField("Subject");
        $cobdata->delField("TextBody");
      }

      else {
        $cobdata->setField("Subject", $emailFields["subject"]);
        $cobdata->setField("TextBody", $emailFields["message"]);
        $cobdata->setField("ReplyTo", $emailFields["from_address"]);
        $cobdata->delField("TemplateID");
        $cobdata->delField("TemplateModel");
      }

      if (!empty($emailFields['tag'])) {
        $cobdata->setField("Tag", $emailFields['tag']);
      }

      $emailFields["postmark_data"] = $cobdata;

    }

    if ($this->debug) {
      \Drupal::logger("bos_email:PostmarkAPI")
        ->info("Email prepped {$this->server}:<br>" . json_encode($emailFields["postmark_data"]->data()));
    }

    // Validate the email data
    $emailFields["postmark_data"]->validate();
    if ($emailFields["postmark_data"]->hasValidationErrors()) {
      $this->error = implode(", ", $emailFields["postmark_data"]->getValidationErrors());
      return FALSE;
    }

    return TRUE;

  }

  /**
   * Send the email via Postmark.
   *
   * @param \Drupal\bos_email\CobEmail $mailobj The email object
   *
   * @return array
   */
  private function sendEmail(CobEmail $email) {

    /**
     * @var $mailobj CobEmail
     */

    // Extract the email object, and validate.
    try {
      $mailobj = $email->data();
    }
    catch (\Exception $e) {}

    if ($email->hasValidationErrors()) {
      return [
        'status' => 'failed',
        'response' => implode(":", $email->getValidationErrors()),
      ];

    }

    // Send the email.
    $postmark_ops = new PostmarkOps();
    $sent = $postmark_ops->sendEmail($mailobj);

    if (!$sent) {
      // Add email data to queue because of Postmark failure.
      $mailobj["postmark_error"] = $postmark_ops->error;
      $this->addQueueItem($mailobj);

      if ($this->debug) {
        \Drupal::logger("bos_email:PostmarkAPI")->info("Queued {$email->getField("server")}");
      }

      $response_message = self::MESSAGE_QUEUED;

    }
    else {
      // Message was sent successfully to sender via Postmark.
      $response_message = self::MESSAGE_SENT;
    }

    return [
      'status' => 'success',
      'response' => $response_message,
    ];

  }

  /**
   * Begin script and API operations when a session object has been secured.
   *
   * @param string $service
   *   The server being called via the endpoint uri.
   *
   * @return CacheableJsonResponse
   *   The json response to send to the endpoint caller.
   */
  public function beginSession(string $service) {
    $token = new TokenOps();
    $data = $this->request->getCurrentRequest()->get('email');
    $data_token = $token->tokenGet($data["token_session"]);

    if ($data_token["token_session"]) {
      // remove token session from DB to prevent reuse
      $token->tokenRemove($data["token_session"]);
      // begin normal email submission
      return $this->begin($service);
    }
    else {
      PostmarkOps::alertHandler($data, [], "", [], "sessiontoken");
      return new CacheableJsonResponse([
        'status' => 'error',
        'response' => 'invalid token',
      ], Response::HTTP_FORBIDDEN);
    }
  }

  /**
   * Begin script and API operations.
   *
   * @param string $service
   *   The server being called via the endpoint uri.
   *
   * @return CacheableJsonResponse
   *   The json response to send to the endpoint caller.
   */
  public function begin(string $service = 'contactform') {

    $this->debug = str_contains($this->request->getCurrentRequest()
      ->getHttpHost(), "lndo.site");
    $response_array = [];

    if (in_array($service, ["contactform", "registry"])) {
      // This is done for legacy reasons (endpoint already in production and
      // in lowercase)
      $service = ucwords($service);
    }

    $this->server = $service;
    if (class_exists("Drupal\\bos_email\\Templates\\{$service}") === TRUE) {
      $this->template_class = "Drupal\\bos_email\\Templates\\{$service}";
      $this->server = $this->template_class::postmarkServer();
      $this->honeypot = $this->template_class::honeypot() ?: "";
    }

    if ($this->debug) {
      \Drupal::logger("bos_email:PostmarkAPI")->info("Starts {$service}");
    }

    if ($this->request->getCurrentRequest()->getMethod() == "POST") {

      // Get the request payload.
      $payload = $this->request->getCurrentRequest()->get('email');

      // Check the honeypot if there is one.
      if (!empty($this->honeypot) && !empty($payload[$this->honeypot])) {
        PostmarkOps::alertHandler($payload, [], "", [], "honeypot");
        return new CacheableJsonResponse([
          'status' => 'success',
          'response' => str_replace(".", "!", self::MESSAGE_SENT),
        ], Response::HTTP_OK);
      }

      // Logging
      if ($this->debug) {
        \Drupal::logger("bos_email:PostmarkAPI")
          ->info("Set data {$service}:<br/>" . json_encode($payload));
      }

      // cleanup the session tokens.
      if (!empty($payload["token_session"])) {
        unset($payload["token_session"]);
      }

      if ($this->authenticate()) {
        // Format and validate the message body.
        if ($this->formatEmail($payload)) {
          // Send email.
          $response_array = $this->sendEmail($payload["postmark_data"]);
        }
        else {
          PostmarkOps::alertHandler($payload, [], "", [], $this->error);
          return new CacheableJsonResponse([
            'status' => 'error',
            'response' => $this->error,
          ], Response::HTTP_BAD_REQUEST);
        }
      }

      else {
        PostmarkOps::alertHandler($payload, [], "", [], "authtoken");
        return new CacheableJsonResponse([
          'status' => 'error',
          'response' => 'could not authenticate',
        ], Response::HTTP_UNAUTHORIZED);
      }

    }
    else {
      return new CacheableJsonResponse([
        'status' => 'error',
        'response' => 'no post data',
      ], Response::HTTP_BAD_REQUEST);
    };

    // Logging
    if ($this->debug) {
      \Drupal::logger("bos_email:PostmarkAPI")
        ->info("Finished {$service}: " . json_encode($response_array));
    }

    if (!empty($response_array)) {
      return new CacheableJsonResponse($response_array, Response::HTTP_OK);
    }
    else {
      return new CacheableJsonResponse(["error" => "Unknown"], Response::HTTP_BAD_REQUEST);
    }
  }

  /**
   * Handles incoming webhooks from PostMark.
   *
   * @param string $service The Postmark server originating the callback/webhook.
   * @param string $stream The server stream originating the callback/webhook.
   *
   * @return Response
   */
  public function callback(string $service, string $stream) {
    /*
     * Sample OOO reply from postmark
     * {
     *   "event": {
     *     "method":"POST",
     *     "path":"/",
     *     "query":{},
     *     "client_ip":"18.217.206.57",
     *     "url":"https://eo6wz7rzjvxvozl.m.pipedream.net/",
     *     "headers":{
     *       "host":"eo6wz7rzjvxvozl.m.pipedream.net",
     *       "content-length":"27606",
     *       "accept":"application/json",
     *       "user-agent":"Postmark",
     *       "content-type":"application/json"
     *     },
     *     "body":{
     *       "FromName":"Info",
     *       "MessageStream":"inbound",
     *       "From":"Info@bphc.org",
     *       "FromFull":{
     *         "Email":"Info@bphc.org",
     *         "Name":"Info",
     *         "MailboxHash":""
     *       },
     *       "To":"\"Boston.gov Contact Form\" <97aapvpkquww@contactform.boston.gov>",
     *       "ToFull":[
     *         {
     *           "Email":"97aapvpkquww@contactform.boston.gov",
     *           "Name":"Boston.gov Contact Form",
     *           "MailboxHash":""
     *         }
     *       ],
     *       "Cc":"",
     *       "CcFull":[],
     *       "Bcc":"",
     *       "BccFull":[],
     *       "OriginalRecipient":"97aapvpkquww@contactform.boston.gov",
     *       "Subject":"Automatic reply: swtrutharticle@gmail.com",
     *       "MessageID":"5830dbbf-69da-4b65-a263-946cd04b5736",
     *       "ReplyTo":"",
     *       "MailboxHash":"",
     *       "Date":"Wed, 26 Apr 2023 14:58:03 +0000",
     *       "TextBody":"Thank you for contacting the Boston Public Health Commission (BPHC). We received your email and BPHC staff will respond to your message within three business days. If your email requires a faster response, Boston residents can call:\n\n· BPHC Main Line: (617) 534-5395 (Monday through Friday, 9am to 5pm)\n· Mayor's Hotline: (617) 635-4500 (after 5pm, during the weekends)\n\nDuring the current COVID-19 public health crisis, BPHC continues to protect, preserve, and promote the health and well-being of all Boston residents, particularly the most vulnerable. If you are a Boston resident with questions about COVID-19, you can find the most current information about COVID-19, testing, vaccines and boosters, and best health practices at our website, www.boston.gov/covid19\n\nTo find information about COVID-19 testing sites in Boston, please visit our COVID-19 Testing Site Page: www.boston.gov/covid19-testing\n\nTo find information about COVID-19 vaccine and booster sites in Boston, please visit our COVID-19 Vaccine Page: www.boston.gov/covid19-vaccines\n\nIf you are seeking a replacement COVID-19 vaccine card, you will need to reach out to the vaccine administer directly for a copy of your records. CIC Health administered vaccines at the Reggie Lewis Center Vaccine Site. You may contact them directly with vaccine questions at (888) 623-3830 or by emailing vaccine-support@cic-health.com\n\nFor those living outside Boston please contact the Massachusetts Department of Public Health at (617) 624-6000 or visit their website, www.mass.gov/covid19.\n\nPlease use the following additional resources for assistance:\n\n\n1) Boston residents call Mayor's Health Line: 617-534-5050/ Toll free: 1-800-847-0710 for information about finding a primary care provider; applying for health insurance; food pantries; Boston Public Health School lunch sites; COVID symptoms; COVID19 cleaning practices; when to call your doctor v. emergency room; donating medical supplies; and other related information.\n\n2) Boston residents call \"311\" for information about parking rules and tickets; needle/litter clean up; street cleaning; getting rid of a big item; report a broken street sign; and non-emergency COVID-related issues.\n\n3) Massachusetts residents call \"211\" for information about testing sites; COVID19 symptoms; Latest state-wide orders; benefit programs (SNAP, unemployment), Call2Talk - over the phone behavioral health services/support; and other related information.\n",
     *       "HtmlBody":"<html>\n<head>\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">\n</head>\n<body>\nThank you for contacting the Boston Public Health Commission (BPHC). We received your email and BPHC staff will respond to your message within three business days. If your email requires a faster response, Boston residents can call:<br>\n<br>\n· BPHC Main Line: (617) 534-5395 (Monday through Friday, 9am to 5pm)<br>\n· Mayor's Hotline: (617) 635-4500 (after 5pm, during the weekends)<br>\n<br>\nDuring the current COVID-19 public health crisis, BPHC continues to protect, preserve, and promote the health and well-being of all Boston residents, particularly the most vulnerable. If you are a Boston resident with questions about COVID-19, you can find\n the most current information about COVID-19, testing, vaccines and boosters, and best health practices at our website, www.boston.gov/covid19 &nbsp;<br>\n<br>\nTo find information about COVID-19 testing sites in Boston, please visit our COVID-19 Testing Site Page: www.boston.gov/covid19-testing<br>\n<br>\nTo find information about COVID-19 vaccine and booster sites in Boston, please visit our COVID-19 Vaccine Page: www.boston.gov/covid19-vaccines<br>\n<br>\nIf you are seeking a replacement COVID-19 vaccine card, you will need to reach out to the vaccine administer directly for a copy of your records. CIC Health administered vaccines at the Reggie Lewis Center Vaccine Site. You may contact them directly with vaccine\n questions at (888) 623-3830 or by emailing vaccine-support@cic-health.com<br>\n<br>\nFor those living outside Boston please contact the Massachusetts Department of Public Health at (617) 624-6000 or visit their website, www.mass.gov/covid19.<br>\n<br>\nPlease use the following additional resources for assistance:<br>\n<br>\n<br>\n1) Boston residents call Mayor's Health Line: 617-534-5050/ Toll free: 1-800-847-0710 for information about finding a primary care provider; applying for health insurance; food pantries; Boston Public Health School lunch sites; COVID symptoms; COVID19 cleaning\n practices; when to call your doctor v. emergency room; donating medical supplies; and other related information.<br>\n<br>\n2) Boston residents call &quot;311&quot; for information about parking rules and tickets; needle/litter clean up; street cleaning; getting rid of a big item; report a broken street sign; and non-emergency COVID-related issues.<br>\n<br>\n3) Massachusetts residents call &quot;211&quot; for information about testing sites; COVID19 symptoms; Latest state-wide orders; benefit programs (SNAP, unemployment), Call2Talk - over the phone behavioral health services/support; and other related information.\n</body>\n</html>\n",
     *       "StrippedTextReply":"",
     *       "RawEmail":"Received: by p-pm-inboundg02a-aws-useast1a.inbound.postmarkapp.com (Postfix, from userid 996)\n\tid 388B8406077; Wed, 26 Apr 2023 14:58:07 +0000 (UTC)\nX-Spam-Checker-Version: SpamAssassin 3.4.0 (2014-02-07) on\n\tp-pm-inboundg02a-aws-useast1a\nX-Spam-Status: No\nX-Spam-Score: -3.1\nX-Spam-Tests: DKIM_SIGNED,DKIM_VALID,DKIM_VALID_AU,FORGED_SPF_HELO,\n\tHTML_MESSAGE,PDS_BAD_THREAD_QP_64,RCVD_IN_DNSWL_HI,\n\tRCVD_IN_ZEN_BLOCKED_OPENDNS,SPF_HELO_PASS,T_SCC_BODY_TEXT_LINE,\n\tURIBL_DBL_BLOCKED_OPENDNS,URIBL_ZEN_BLOCKED_OPENDNS\nReceived-SPF: pass (gcc02-dm3-obe.outbound.protection.outlook.com: Sender is authorized to use 'GCC02-DM3-obe.outbound.protection.outlook.com' in 'helo' identity (mechanism 'include:spf.protection.outlook.com' matched)) receiver=p-pm-inboundg02a-aws-useast1a; identity=helo; helo=GCC02-DM3-obe.outbound.protection.outlook.com; client-ip=52.100.154.204\nReceived: from GCC02-DM3-obe.outbound.protection.outlook.com (mail-dm3gcc02hn2204.outbound.protection.outlook.com [52.100.154.204])\n\t(using TLSv1.2 with cipher ECDHE-RSA-AES256-GCM-SHA384 (256/256 bits))\n\t(No client certificate requested)\n\tby p-pm-inboundg02a-aws-useast1a.inbound.postmarkapp.com (Postfix) with ESMTPS id 5BEC94391AC\n\tfor <97aapvpkquww@contactform.boston.gov>; Wed, 26 Apr 2023 14:58:06 +0000 (UTC)\nARC-Seal: i=1; a=rsa-sha256; s=arcselector9901; d=microsoft.com; cv=none;\n b=U5dkmtEewrpMIUkUcp/0pMmXjGehVhBJFRjh1yvrdVYi/Ohh0rCYWOwEVVDOJTEcOUln/iX/kO4EH/8ieTRFmTA8rPCh1fxtbhaeRBdBlCrt5VPQsh+gNwcFarXKTlShnehtLVHQFEuhnJPkx77xBxxsgnew8Mq3q720D3t8syGMCf4I0S5cjYR0YLiwuw8Y4cjSoGaj9CJ4MMVgoO+2Z1EpG9FTFc3C/Bky3CEo/MOO2wc1IHiErneM3vFEh63oTdTnm+PMLNuzVcGhjM57QaBNnQtiuL+5nUC8L88M3X4a5gWGywtt8GJ0Ftp2DlpkWRn9XTn1I8iaGxKNO0cjkQ==\nARC-Message-Signature: i=1; a=rsa-sha256; c=relaxed/relaxed; d=microsoft.com;\n s=arcselector9901;\n h=From:Date:Subject:Message-ID:Content-Type:MIME-Version:X-MS-Exchange-AntiSpam-MessageData-ChunkCount:X-MS-Exchange-AntiSpam-MessageData-0:X-MS-Exchange-AntiSpam-MessageData-1;\n bh=wkfTujigPz9Vsri2KGM4thIGpM7UPDBMg/X+eaSprvY=;\n b=aQWcXNf9HmQRCRCxOu9zNYbgzYkUPsvZ098tKYTOpehNlq4icP4ik5oLmt7rySHo1WKD9bqMV60AVytE4q/wtOyT1bQxSJxc+qK2kBdtt+uj/edTO8cG9vTT/V988ZuJAfs21/0nhuZJUGr2sJqCU1k2NVw5T7peGDnmr37Aprd69VFauu7tkaWlFJCV1A5KWwGGoXy3xp/0jRSSZUAPnCDBLmxaCdf/3UxFv7Iv5gcodAIzgUjJsiUd+ax5TmPZuJnnKZNvdXz6bFHpJBdhDQAsi8Rub6fF6BXYyV23EQqEOE24Zx4HpOisCcasC6VvUzfrOl10WkSiCIAcrfaoJA==\nARC-Authentication-Results: i=1; mx.microsoft.com 1; spf=none; dmarc=pass\n action=none header.from=bphc.org; dkim=pass header.d=bphc.org; arc=none\nDKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed; d=bphc.org; s=selector2;\n h=From:Date:Subject:Message-ID:Content-Type:MIME-Version:X-MS-Exchange-SenderADCheck;\n bh=wkfTujigPz9Vsri2KGM4thIGpM7UPDBMg/X+eaSprvY=;\n b=bPQuW8bHQ8ik60o0vemknvbqbSCqNDrsmbV5m/hUAswIkaB5wC+DDbV0lxls5aL1ud4K2+BpkxI6dfiMs524vg+iEN2taeNutFIwD6DRJBLScI6aMM+KsMaiOpMOMsfME/WEuv0awiPnOGzpIqz2gyTjoJ99OOjgo7Vla49yDl8=\nReceived: from DM8PR09MB7205.namprd09.prod.outlook.com (2603:10b6:5:2ea::17)\n by SA9PR09MB5853.namprd09.prod.outlook.com (2603:10b6:806:43::12) with\n Microsoft SMTP Server (version=TLS1_2,\n cipher=TLS_ECDHE_RSA_WITH_AES_256_GCM_SHA384) id 15.20.6340.22; Wed, 26 Apr\n 2023 14:58:03 +0000\nReceived: from DM8PR09MB7205.namprd09.prod.outlook.com ([::1]) by\n DM8PR09MB7205.namprd09.prod.outlook.com ([fe80::f0e6:d90d:78e5:d6ad%6]) with\n Microsoft SMTP Server id 15.20.6340.022; Wed, 26 Apr 2023 14:58:03 +0000\nFrom: Info <Info@bphc.org>\nTo: Boston.gov Contact Form <97aapvpkquww@contactform.boston.gov>\nSubject: Automatic reply: swtrutharticle@gmail.com\nThread-Topic: swtrutharticle@gmail.com\nThread-Index: AQHZeE9/uR0mx9Kq3UimCKHLEgf/WK89rmIb\nDate: Wed, 26 Apr 2023 14:58:03 +0000\nMessage-ID:\n <aef8de8b87ce408d98043942ab89c729@DM8PR09MB7205.namprd09.prod.outlook.com>\nReferences: <c6d81b7d-04b6-4c80-8db5-66d195cd0644@mtasv.net>\nIn-Reply-To: <c6d81b7d-04b6-4c80-8db5-66d195cd0644@mtasv.net>\nX-MS-Has-Attach:\nX-Auto-Response-Suppress: All\nX-MS-Exchange-Inbox-Rules-Loop: info@bphc.org\nX-MS-TNEF-Correlator:\nauthentication-results: dkim=none (message not signed)\n header.d=none;dmarc=none action=none header.from=bphc.org;\nx-ms-exchange-parent-message-id:\n <c6d81b7d-04b6-4c80-8db5-66d195cd0644@mtasv.net>\nauto-submitted: auto-generated\nx-ms-exchange-generated-message-source: Mailbox Rules Agent\nx-ms-publictraffictype: Email\nx-ms-traffictypediagnostic: DM8PR09MB7205:EE_|SA9PR09MB5853:EE_\nx-ms-office365-filtering-correlation-id: 2d0e874b-548e-4cc4-a34c-08db4666a30b\nx-ms-exchange-senderadcheck: 1\nx-ms-exchange-antispam-relay: 0\nx-microsoft-antispam: BCL:0;\nx-microsoft-antispam-message-info:\n hdKj+yxLqwFw9C4lmhHWSiL41i0Ym1CjqLq6kYkNqoy0lfyvpvY0NM2whKJz97b9lLAsVnSoNDt8Ps/qVfhj7omjaMrfqCB7y9kKvAV9RvhP1CedQqgZRbYyQdBNCWxI5bUSk2zJGvBe6S/iDUh4oz7oRcY4e1FrL8TTATwxysEfocryu4qDqlh4kxs2hEYq82xPpF5yswm31r3XeV+znNLQk7CbsZGaikqGMW3QiGTygPCq8RU1YW6enxavKtOjcR4fw07E4yO89QErHGx3e0OspddJBpDdZiOxS6u/b90xjjk8lrlDXNrq6X8Hi1tPDpGpMj9bBNMoGhfZAXhVK+A6QAws557nchhSXakP/Ww6J7ZMQuK/pRv8j8fuzs3DShZTkP9QGTbBEcoAFuJDTmjmUIsJVwQR7s9Upbgx4auzZrb4xJnK0FaC/s+Q7N6O98pUyukwb967dGtHr3Nbk8o42xP3pRnlCpaKPmVcysL8NFu/3EixKC9NzloSaj2nTHtbdQbUV5x8aWEkcoAt6cLmt8wpjsbTum79ud02PCxPAttFFHNIui9ZynY6cTL3fdtP0y9UtqTWWfd49jKXD/wWP3SmIH03neIDUwCr9hVgrSuIix8t+TK4U6VlTX1q\nx-forefront-antispam-report:\n CIP:255.255.255.255;CTRY:;LANG:en;SCL:1;SRV:;IPV:NLI;SFV:NSPM;H:DM8PR09MB7205.namprd09.prod.outlook.com;PTR:;CAT:NONE;SFS:(13230028)(50650200015)(366004)(396003)(39830400003)(136003)(451199021)(122000001)(41300700001)(9686003)(6506007)(5660300002)(8936002)(8676002)(83380400001)(42882007)(2906002)(6916009)(66946007)(66556008)(66476007)(66446008)(64756008)(78352004)(88996005)(508600001)(108616005)(24736004)(55016003)(41320700001)(7696005)(15974865002)(40140700001)(71200400001)(111220200008)(80100003)(220923002);DIR:OUT;SFP:1501;\nx-ms-exchange-antispam-messagedata-chunkcount: 1\nx-ms-exchange-antispam-messagedata-0:\n pPxBUfEyrLD21SOOd4tLkIYg3hNnul4nioPGm0suaLPLh6ibqSrMPJs+cpc5flv0/UCpRhGcahUqqwsPOVMXkookAGud5vKKm7+IfXMt/BAI2DZ7sNGSTINWPmANSRfT6FdCWG8ZKB7C5mFAuRqUA+0nLLAcpYBgClENmOn0Shpl97lf9RjVVUg9mp/Umk3QG8fyn4UraI+Tk90bPH7hktNI76t/e+uePZrOfqQ90thzRRnRUCL7/0/gc0F1xegMot0S5C0MQm6J/4B7COPUUj/obVrXW+/24/qQ+EMa8HOGjaZpwgkNkfW308qsLpam\nContent-Type: multipart/alternative;\n\tboundary=\"_000_aef8de8b87ce408d98043942ab89c729DM8PR09MB7205namprd09pr_\"\nMIME-Version: 1.0\nX-OriginatorOrg: bphc.org\nX-MS-Exchange-CrossTenant-AuthAs: Internal\nX-MS-Exchange-CrossTenant-AuthSource: DM8PR09MB7205.namprd09.prod.outlook.com\nX-MS-Exchange-CrossTenant-Network-Message-Id: 2d0e874b-548e-4cc4-a34c-08db4666a30b\nX-MS-Exchange-CrossTenant-originalarrivaltime: 26 Apr 2023 14:58:03.8958\n (UTC)\nX-MS-Exchange-CrossTenant-fromentityheader: Hosted\nX-MS-Exchange-CrossTenant-id: ff5b5bc8-925b-471f-942a-eb176c03ab36\nX-MS-Exchange-Transport-CrossTenantHeadersStamped: SA9PR09MB5853\n\n--_000_aef8de8b87ce408d98043942ab89c729DM8PR09MB7205namprd09pr_\nContent-Type: text/plain; charset=\"iso-8859-1\"\nContent-Transfer-Encoding: quoted-printable\n\nThank you for contacting the Boston Public Health Commission (BPHC). We rec=\neived your email and BPHC staff will respond to your message within three b=\nusiness days. If your email requires a faster response, Boston residents ca=\nn call:\n\n=B7 BPHC Main Line: (617) 534-5395 (Monday through Friday, 9am to 5pm)\n=B7 Mayor's Hotline: (617) 635-4500 (after 5pm, during the weekends)\n\nDuring the current COVID-19 public health crisis, BPHC continues to protect=\n, preserve, and promote the health and well-being of all Boston residents, =\nparticularly the most vulnerable. If you are a Boston resident with questio=\nns about COVID-19, you can find the most current information about COVID-19=\n, testing, vaccines and boosters, and best health practices at our website,=\n www.boston.gov/covid19\n\nTo find information about COVID-19 testing sites in Boston, please visit ou=\nr COVID-19 Testing Site Page: www.boston.gov/covid19-testing\n\nTo find information about COVID-19 vaccine and booster sites in Boston, ple=\nase visit our COVID-19 Vaccine Page: www.boston.gov/covid19-vaccines\n\nIf you are seeking a replacement COVID-19 vaccine card, you will need to re=\nach out to the vaccine administer directly for a copy of your records. CIC =\nHealth administered vaccines at the Reggie Lewis Center Vaccine Site. You m=\nay contact them directly with vaccine questions at (888) 623-3830 or by ema=\niling vaccine-support@cic-health.com\n\nFor those living outside Boston please contact the Massachusetts Department=\n of Public Health at (617) 624-6000 or visit their website, www.mass.gov/co=\nvid19.\n\nPlease use the following additional resources for assistance:\n\n\n1) Boston residents call Mayor's Health Line: 617-534-5050/ Toll free: 1-80=\n0-847-0710 for information about finding a primary care provider; applying =\nfor health insurance; food pantries; Boston Public Health School lunch site=\ns; COVID symptoms; COVID19 cleaning practices; when to call your doctor v. =\nemergency room; donating medical supplies; and other related information.\n\n2) Boston residents call \"311\" for information about parking rules and tick=\nets; needle/litter clean up; street cleaning; getting rid of a big item; re=\nport a broken street sign; and non-emergency COVID-related issues.\n\n3) Massachusetts residents call \"211\" for information about testing sites; =\nCOVID19 symptoms; Latest state-wide orders; benefit programs (SNAP, unemplo=\nyment), Call2Talk - over the phone behavioral health services/support; and =\nother related information.\n\n--_000_aef8de8b87ce408d98043942ab89c729DM8PR09MB7205namprd09pr_\nContent-Type: text/html; charset=\"iso-8859-1\"\nContent-Transfer-Encoding: quoted-printable\n\n<html>\n<head>\n<meta http-equiv=3D\"Content-Type\" content=3D\"text/html; charset=3Diso-8859-=\n1\">\n</head>\n<body>\nThank you for contacting the Boston Public Health Commission (BPHC). We rec=\neived your email and BPHC staff will respond to your message within three b=\nusiness days. If your email requires a faster response, Boston residents ca=\nn call:<br>\n<br>\n=B7 BPHC Main Line: (617) 534-5395 (Monday through Friday, 9am to 5pm)<br>\n=B7 Mayor's Hotline: (617) 635-4500 (after 5pm, during the weekends)<br>\n<br>\nDuring the current COVID-19 public health crisis, BPHC continues to protect=\n, preserve, and promote the health and well-being of all Boston residents, =\nparticularly the most vulnerable. If you are a Boston resident with questio=\nns about COVID-19, you can find\n the most current information about COVID-19, testing, vaccines and booster=\ns, and best health practices at our website, www.boston.gov/covid19 &nbsp;<=\nbr>\n<br>\nTo find information about COVID-19 testing sites in Boston, please visit ou=\nr COVID-19 Testing Site Page: www.boston.gov/covid19-testing<br>\n<br>\nTo find information about COVID-19 vaccine and booster sites in Boston, ple=\nase visit our COVID-19 Vaccine Page: www.boston.gov/covid19-vaccines<br>\n<br>\nIf you are seeking a replacement COVID-19 vaccine card, you will need to re=\nach out to the vaccine administer directly for a copy of your records. CIC =\nHealth administered vaccines at the Reggie Lewis Center Vaccine Site. You m=\nay contact them directly with vaccine\n questions at (888) 623-3830 or by emailing vaccine-support@cic-health.com<=\nbr>\n<br>\nFor those living outside Boston please contact the Massachusetts Department=\n of Public Health at (617) 624-6000 or visit their website, www.mass.gov/co=\nvid19.<br>\n<br>\nPlease use the following additional resources for assistance:<br>\n<br>\n<br>\n1) Boston residents call Mayor's Health Line: 617-534-5050/ Toll free: 1-80=\n0-847-0710 for information about finding a primary care provider; applying =\nfor health insurance; food pantries; Boston Public Health School lunch site=\ns; COVID symptoms; COVID19 cleaning\n practices; when to call your doctor v. emergency room; donating medical su=\npplies; and other related information.<br>\n<br>\n2) Boston residents call &quot;311&quot; for information about parking rule=\ns and tickets; needle/litter clean up; street cleaning; getting rid of a bi=\ng item; report a broken street sign; and non-emergency COVID-related issues=\n.<br>\n<br>\n3) Massachusetts residents call &quot;211&quot; for information about testi=\nng sites; COVID19 symptoms; Latest state-wide orders; benefit programs (SNA=\nP, unemployment), Call2Talk - over the phone behavioral health services/sup=\nport; and other related information.\n</body>\n</html>\n\n--_000_aef8de8b87ce408d98043942ab89c729DM8PR09MB7205namprd09pr_--\n",
     *       "Tag":"",
     *       "Headers":[
     *         {
     *           "Name":"Return-Path",
     *           "Value":"<MAILER-DAEMON>"
     *         },
     *         {
     *           "Name":"Received",
     *           "Value":"by p-pm-inboundg02a-aws-useast1a.inbound.postmarkapp.com (Postfix, from userid 996)\tid 388B8406077; Wed, 26 Apr 2023 14:58:07 +0000 (UTC)"
     *         },
     *         {
     *           "Name":"X-Spam-Checker-Version",
     *           "Value":"SpamAssassin 3.4.0 (2014-02-07) on\tp-pm-inboundg02a-aws-useast1a"
     *         },
     *         {
     *           "Name":"X-Spam-Status",
     *           "Value":"No"
     *         },
     *         {
     *           "Name":"X-Spam-Score",
     *           "Value":"-3.1"
     *         },
     *         {
     *           "Name":"X-Spam-Tests",
     *           "Value":"DKIM_SIGNED,DKIM_VALID,DKIM_VALID_AU,FORGED_SPF_HELO,\tHTML_MESSAGE,PDS_BAD_THREAD_QP_64,RCVD_IN_DNSWL_HI,\tRCVD_IN_ZEN_BLOCKED_OPENDNS,SPF_HELO_PASS,T_SCC_BODY_TEXT_LINE,\tURIBL_DBL_BLOCKED_OPENDNS,URIBL_ZEN_BLOCKED_OPENDNS"
     *         },
     *         {
     *           "Name":"Received-SPF",
     *           "Value":"pass (gcc02-dm3-obe.outbound.protection.outlook.com: Sender is authorized to use 'GCC02-DM3-obe.outbound.protection.outlook.com' in 'helo' identity (mechanism 'include:spf.protection.outlook.com' matched)) receiver=p-pm-inboundg02a-aws-useast1a; identity=helo; helo=GCC02-DM3-obe.outbound.protection.outlook.com; client-ip=52.100.154.204"
     *         },
     *         {
     *           "Name":"Received",
     *           "Value":"from GCC02-DM3-obe.outbound.protection.outlook.com (mail-dm3gcc02hn2204.outbound.protection.outlook.com [52.100.154.204])\t(using TLSv1.2 with cipher ECDHE-RSA-AES256-GCM-SHA384 (256/256 bits))\t(No client certificate requested)\tby p-pm-inboundg02a-aws-useast1a.inbound.postmarkapp.com (Postfix) with ESMTPS id 5BEC94391AC\tfor <97aapvpkquww@contactform.boston.gov>; Wed, 26 Apr 2023 14:58:06 +0000 (UTC)"
     *         },
     *         {
     *           "Name":"ARC-Seal",
     *           "Value":"i=1; a=rsa-sha256; s=arcselector9901; d=microsoft.com; cv=none; b=U5dkmtEewrpMIUkUcp/0pMmXjGehVhBJFRjh1yvrdVYi/Ohh0rCYWOwEVVDOJTEcOUln/iX/kO4EH/8ieTRFmTA8rPCh1fxtbhaeRBdBlCrt5VPQsh+gNwcFarXKTlShnehtLVHQFEuhnJPkx77xBxxsgnew8Mq3q720D3t8syGMCf4I0S5cjYR0YLiwuw8Y4cjSoGaj9CJ4MMVgoO+2Z1EpG9FTFc3C/Bky3CEo/MOO2wc1IHiErneM3vFEh63oTdTnm+PMLNuzVcGhjM57QaBNnQtiuL+5nUC8L88M3X4a5gWGywtt8GJ0Ftp2DlpkWRn9XTn1I8iaGxKNO0cjkQ=="
     *         },
     *         {
     *           "Name":"ARC-Message-Signature",
     *           "Value":"i=1; a=rsa-sha256; c=relaxed/relaxed; d=microsoft.com; s=arcselector9901; h=From:Date:Subject:Message-ID:Content-Type:MIME-Version:X-MS-Exchange-AntiSpam-MessageData-ChunkCount:X-MS-Exchange-AntiSpam-MessageData-0:X-MS-Exchange-AntiSpam-MessageData-1; bh=wkfTujigPz9Vsri2KGM4thIGpM7UPDBMg/X+eaSprvY=; b=aQWcXNf9HmQRCRCxOu9zNYbgzYkUPsvZ098tKYTOpehNlq4icP4ik5oLmt7rySHo1WKD9bqMV60AVytE4q/wtOyT1bQxSJxc+qK2kBdtt+uj/edTO8cG9vTT/V988ZuJAfs21/0nhuZJUGr2sJqCU1k2NVw5T7peGDnmr37Aprd69VFauu7tkaWlFJCV1A5KWwGGoXy3xp/0jRSSZUAPnCDBLmxaCdf/3UxFv7Iv5gcodAIzgUjJsiUd+ax5TmPZuJnnKZNvdXz6bFHpJBdhDQAsi8Rub6fF6BXYyV23EQqEOE24Zx4HpOisCcasC6VvUzfrOl10WkSiCIAcrfaoJA=="
     *         },
     *         {
     *           "Name":"ARC-Authentication-Results",
     *           "Value":"i=1; mx.microsoft.com 1; spf=none; dmarc=pass action=none header.from=bphc.org; dkim=pass header.d=bphc.org; arc=none"
     *         },
     *         {
     *           "Name":"DKIM-Signature",
     *           "Value":"v=1; a=rsa-sha256; c=relaxed/relaxed; d=bphc.org; s=selector2; h=From:Date:Subject:Message-ID:Content-Type:MIME-Version:X-MS-Exchange-SenderADCheck; bh=wkfTujigPz9Vsri2KGM4thIGpM7UPDBMg/X+eaSprvY=; b=bPQuW8bHQ8ik60o0vemknvbqbSCqNDrsmbV5m/hUAswIkaB5wC+DDbV0lxls5aL1ud4K2+BpkxI6dfiMs524vg+iEN2taeNutFIwD6DRJBLScI6aMM+KsMaiOpMOMsfME/WEuv0awiPnOGzpIqz2gyTjoJ99OOjgo7Vla49yDl8="
     *         },
     *         {
     *           "Name":"Received",
     *           "Value":"from DM8PR09MB7205.namprd09.prod.outlook.com (2603:10b6:5:2ea::17) by SA9PR09MB5853.namprd09.prod.outlook.com (2603:10b6:806:43::12) with Microsoft SMTP Server (version=TLS1_2, cipher=TLS_ECDHE_RSA_WITH_AES_256_GCM_SHA384) id 15.20.6340.22; Wed, 26 Apr 2023 14:58:03 +0000"
     *         },
     *         {
     *           "Name":"Received",
     *           "Value":"from DM8PR09MB7205.namprd09.prod.outlook.com ([::1]) by DM8PR09MB7205.namprd09.prod.outlook.com ([fe80::f0e6:d90d:78e5:d6ad%6]) with Microsoft SMTP Server id 15.20.6340.022; Wed, 26 Apr 2023 14:58:03 +0000"
     *         },
     *         {
     *           "Name":"Thread-Topic",
     *           "Value":"swtrutharticle@gmail.com"
     *         },
     *         {
     *           "Name":"Thread-Index",
     *           "Value":"AQHZeE9/uR0mx9Kq3UimCKHLEgf/WK89rmIb"
     *         },
     *         {
     *           "Name":"Message-ID",
     *           "Value":"<aef8de8b87ce408d98043942ab89c729@DM8PR09MB7205.namprd09.prod.outlook.com>"
     *         },
     *         {
     *           "Name":"References",
     *           "Value":"<c6d81b7d-04b6-4c80-8db5-66d195cd0644@mtasv.net>"
     *         },
     *         {
     *           "Name":"In-Reply-To",
     *           "Value":"<c6d81b7d-04b6-4c80-8db5-66d195cd0644@mtasv.net>"
     *         },
     *         {
     *           "Name":"X-MS-Has-Attach",
     *           "Value":""
     *         },
     *         {
     *           "Name":"X-Auto-Response-Suppress",
     *           "Value":"All"
     *         },
     *         {
     *           "Name":"X-MS-Exchange-Inbox-Rules-Loop",
     *           "Value":"info@bphc.org"
     *         },
     *         {
     *           "Name":"X-MS-TNEF-Correlator",
     *           "Value":""
     *         },
     *         {
     *           "Name":"authentication-results",
     *           "Value":"dkim=none (message not signed) header.d=none;dmarc=none action=none header.from=bphc.org;"
     *         },
     *         {
     *           "Name":"x-ms-exchange-parent-message-id",
     *           "Value":"<c6d81b7d-04b6-4c80-8db5-66d195cd0644@mtasv.net>"
     *         },
     *         {
     *           "Name":"auto-submitted",
     *           "Value":"auto-generated"
     *         },
     *         {
     *           "Name":"x-ms-exchange-generated-message-source",
     *           "Value":"Mailbox Rules Agent"
     *         },
     *         {
     *           "Name":"x-ms-publictraffictype",
     *           "Value":"Email"
     *         },
     *         {
     *           "Name":"x-ms-traffictypediagnostic",
     *           "Value":"DM8PR09MB7205:EE_|SA9PR09MB5853:EE_"
     *         },
     *         {
     *           "Name":"x-ms-office365-filtering-correlation-id",
     *           "Value":"2d0e874b-548e-4cc4-a34c-08db4666a30b"
     *         },
     *         {
     *           "Name":"x-ms-exchange-senderadcheck",
     *           "Value":"1"
     *         },
     *         {
     *           "Name":"x-ms-exchange-antispam-relay",
     *           "Value":"0"
     *         },
     *         {
     *           "Name":"x-microsoft-antispam",
     *           "Value":"BCL:0;"
     *         },
     *         {
     *           "Name":"x-microsoft-antispam-message-info",
     *           "Value":"hdKj+yxLqwFw9C4lmhHWSiL41i0Ym1CjqLq6kYkNqoy0lfyvpvY0NM2whKJz97b9lLAsVnSoNDt8Ps/qVfhj7omjaMrfqCB7y9kKvAV9RvhP1CedQqgZRbYyQdBNCWxI5bUSk2zJGvBe6S/iDUh4oz7oRcY4e1FrL8TTATwxysEfocryu4qDqlh4kxs2hEYq82xPpF5yswm31r3XeV+znNLQk7CbsZGaikqGMW3QiGTygPCq8RU1YW6enxavKtOjcR4fw07E4yO89QErHGx3e0OspddJBpDdZiOxS6u/b90xjjk8lrlDXNrq6X8Hi1tPDpGpMj9bBNMoGhfZAXhVK+A6QAws557nchhSXakP/Ww6J7ZMQuK/pRv8j8fuzs3DShZTkP9QGTbBEcoAFuJDTmjmUIsJVwQR7s9Upbgx4auzZrb4xJnK0FaC/s+Q7N6O98pUyukwb967dGtHr3Nbk8o42xP3pRnlCpaKPmVcysL8NFu/3EixKC9NzloSaj2nTHtbdQbUV5x8aWEkcoAt6cLmt8wpjsbTum79ud02PCxPAttFFHNIui9ZynY6cTL3fdtP0y9UtqTWWfd49jKXD/wWP3SmIH03neIDUwCr9hVgrSuIix8t+TK4U6VlTX1q"
     *         },
     *         {
     *           "Name":"x-forefront-antispam-report",
     *           "Value":"CIP:255.255.255.255;CTRY:;LANG:en;SCL:1;SRV:;IPV:NLI;SFV:NSPM;H:DM8PR09MB7205.namprd09.prod.outlook.com;PTR:;CAT:NONE;SFS:(13230028)(50650200015)(366004)(396003)(39830400003)(136003)(451199021)(122000001)(41300700001)(9686003)(6506007)(5660300002)(8936002)(8676002)(83380400001)(42882007)(2906002)(6916009)(66946007)(66556008)(66476007)(66446008)(64756008)(78352004)(88996005)(508600001)(108616005)(24736004)(55016003)(41320700001)(7696005)(15974865002)(40140700001)(71200400001)(111220200008)(80100003)(220923002);DIR:OUT;SFP:1501;"
     *         },
     *         {
     *           "Name":"x-ms-exchange-antispam-messagedata-chunkcount",
     *           "Value":"1"
     *         },
     *         {
     *           "Name":"x-ms-exchange-antispam-messagedata-0",
     *           "Value":"pPxBUfEyrLD21SOOd4tLkIYg3hNnul4nioPGm0suaLPLh6ibqSrMPJs+cpc5flv0/UCpRhGcahUqqwsPOVMXkookAGud5vKKm7+IfXMt/BAI2DZ7sNGSTINWPmANSRfT6FdCWG8ZKB7C5mFAuRqUA+0nLLAcpYBgClENmOn0Shpl97lf9RjVVUg9mp/Umk3QG8fyn4UraI+Tk90bPH7hktNI76t/e+uePZrOfqQ90thzRRnRUCL7/0/gc0F1xegMot0S5C0MQm6J/4B7COPUUj/obVrXW+/24/qQ+EMa8HOGjaZpwgkNkfW308qsLpam"
     *         },
     *         {
     *           "Name":"MIME-Version",
     *           "Value":"1.0"
     *         },
     *         {
     *           "Name":"X-OriginatorOrg",
     *           "Value":"bphc.org"
     *         },
     *         {
     *           "Name":"X-MS-Exchange-CrossTenant-AuthAs",
     *           "Value":"Internal"
     *         },
     *         {
     *           "Name":"X-MS-Exchange-CrossTenant-AuthSource",
     *           "Value":"DM8PR09MB7205.namprd09.prod.outlook.com"
     *         },
     *         {
     *           "Name":"X-MS-Exchange-CrossTenant-Network-Message-Id",
     *           "Value":"2d0e874b-548e-4cc4-a34c-08db4666a30b"
     *         },
     *         {
     *           "Name":"X-MS-Exchange-CrossTenant-originalarrivaltime",
     *           "Value":"26 Apr 2023 14:58:03.8958 (UTC)"
     *         },
     *         {
     *           "Name":"X-MS-Exchange-CrossTenant-fromentityheader",
     *           "Value":"Hosted"
     *         },
     *         {
     *           "Name":"X-MS-Exchange-CrossTenant-id",
     *           "Value":"ff5b5bc8-925b-471f-942a-eb176c03ab36"
     *         },
     *         {
     *           "Name":"X-MS-Exchange-Transport-CrossTenantHeadersStamped",
     *           "Value":"SA9PR09MB5853"
     *         }
     *       ],
     *       "Attachments":[]
     *     }
     *   },
     *   "context":{
     *     "id":"2Oy7hZ1QVBQ3zaAv5i1XyPDlsip",
     *     "ts":"2023-04-26T14:58:07.569Z",
     *     "pipeline_id":null,
     *     "workflow_id":"p_k2CMNzW",
     *     "deployment_id":"d_R6sbKYgy",
     *     "source_type":"COMPONENT",
     *     "verified":false,
     *     "hops":null,
     *     "test":false,
     *     "replay":false,
     *     "owner_id":"o_GOI1d1r",
     *     "platform_version":"3.38.3",
     *     "workflow_name":"RequestBin",
     *     "resume":null,
     *     "trace_id":"2Oy7hahh0o2pBE1ApULPoO2lpnQ"
     *   }
     * }

    */

    $this->debug = Boston::local_mode();

    if ($this->debug) {
      \Drupal::logger("bos_email:PostmarkAPI")->info("Starts {$service} (callback)");
    }

    if ($this->request->getCurrentRequest()->getMethod() == "POST") {

      // Get the request payload.
      $emailFields = $this->request->getCurrentRequest()->getContent();
      $emailFields = (array) json_decode($emailFields);

      // Format the email message.
      if (class_exists("Drupal\\bos_email\\Templates\\{$service}") === TRUE) {

        $this->template_class = "Drupal\\bos_email\\Templates\\{$service}";

        $this->server = self::AUTORESPONDER_SERVERNAME;
        $this->stream = $stream;
        $emailFields["postmark_data"] = new CobEmail([
          "server" => $this->server,
          "postmark_endpoint" => self::POSTMARK_DEFAULT_ENDPOINT,
          "Tag" => $this->stream
        ]);

        $this->template_class::incoming($emailFields);

        // Logging
        if ($this->debug) {
          \Drupal::logger("bos_email:PostmarkAPI")
            ->info("Set data {$service}:<br/>" . json_encode($emailFields));
        }

        $response_array = $this->sendEmail($emailFields["postmark_data"]);

        if ($this->debug) {
          \Drupal::logger("bos_email:PostmarkAPI")
            ->info("Finished {$service}: " . json_encode($response_array));
        }

      }

      if (!empty($response_array)) {
        return new CacheableJsonResponse($response_array, Response::HTTP_OK);
      }
      else {
        return new CacheableJsonResponse(["error" => "Unknown"], Response::HTTP_BAD_REQUEST);
      }

    }

  }

}
