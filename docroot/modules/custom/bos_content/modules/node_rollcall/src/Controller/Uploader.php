<?php

namespace Drupal\node_rollcall\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Entity\EntityInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Uploader class for endpoint.
 *
 */
class Uploader extends ControllerBase {

  private RequestStack $request;

  // Taxonomies cache.
  private array $councillors = [];
  private array $votes = [];

  // Node cache: roll_call_dockets
  private array $dockets = [];

  private EntityInterface|null $active_docket = NULL;
  private bool $active_docket_changed = FALSE;
  private array $stats = [
    "dockets" => [
      "count" => 0,
      "created" => 0,
      "updated" => 0
    ],
    "rollcall_votes" => [
      "count" => 0,
      "created" => 0,
      "updated" => 0
    ],
    "councillors" => [
      "created" => 0,
    ]
  ];

  private CacheableJsonResponse $response;

   /**
   * Public construct for Request.
   */
  public function __construct(RequestStack $request) {
    $this->request = $request;
    // Populate docket cache to avoid repeated entity loading.
    foreach(\Drupal::entityTypeManager()
      ->getStorage("node")
      ->loadByProperties(["type"=>"roll_call_dockets"]) as $docket) {
      if ($dt = strtotime($docket->field_meeting_date->value)) {
        $this->dockets[sprintf("%s-%s", $dt, $docket->getTitle())] = $docket->id();
      }
    }
    // Populate taxomomy cache for Councillor names.
    foreach(\Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('vocab_city_councillors') as $term) {
      $this->councillors[$term->name] = ["tid" => $term->tid];
    }
    // Populate taxonomy cache for Vote types.
    foreach (\Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('vocab_votes') as $term) {
      $this->votes[$term->name] = ["tid" => $term->tid];
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): Uploader|static {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('request_stack')
    );
  }

  public function upload(): CacheableJsonResponse {

    ini_set('memory_limit', '-1');

    $output = $this->validateToken();

    if ($output->getStatusCode() != 200) {
      return $output;
    }

    // Get the data
    if (!$payload = $this->getPayload()) {
      return $this->response;
    }

    foreach($payload as $seq => $vote) {

      $dt = explode(" ", explode("T", $vote->votedate, 2)[0], 2)[0];
      $key = sprintf("%s-%s", strtotime($dt), $vote->docket);
      $subject = str_replace(["\r\n\t", "\r\n", "\n", "\t", "<br>", "<br/>"], " ", $vote->subject);

      //  roll_call_dockets node - Create the node if it does not already exist.
      if (array_key_exists($key, $this->dockets)) {

        // This docket is already cached
        $id = $this->dockets[$key];

        // Are we still adding votes to the active docket?
        if (empty($this->active_docket) || $this->active_docket->id() != $id) {
          $this->saveDocketIfChanged();
          $this->active_docket = $this->loadNode($id);
        }

      }
      else {

        // This docket is not cached, so we need to create a new docket.
        $this->saveDocketIfChanged();

        // Convert date into a valid string.
        $dt = date("Y-m-d", strtotime($dt));

        $docketobj = [
          "docket" => $vote->docket,
          "subject" => trim($subject),
          "votedate" => $dt
        ];

        if (!$this->active_docket = $this->createDocket($docketobj)) {
          return $this->response;
        }

        $id = $this->active_docket->id();
        $this->dockets[$key] = $id;

      }

      // Add this vote to the docket.
      $voteobj = [
        "councillor" => ucwords(trim($vote->councillor)),
        "vote" => strtolower(trim($vote->vote))
      ];
      if (!$this->createVote($voteobj)) {
        return $this->response;
      }

      unset($payload[$seq]);

    }

    $this->saveDocketIfChanged();

    return new CacheableJsonResponse([
        'status' => 'ok',
        'response' => "Uploaded records",
        'detail' => $this->stats
      ], 200);

  }

  /**
   * Expect a token to be sent in as part of the querystring, or part of the header.
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *
   */
  private function validateToken(): CacheableJsonResponse {

    if ($apiKey = $this->request->getCurrentRequest()->headers->get("authorization")) {
      $apiKey = explode(" ", $apiKey)[1];
    }
    else {
      $apiKey = $this->request->getCurrentRequest()->get('api_key');
    }

    $token = \Drupal::config("node_rollcall.settings")->get("auth_token");

    //    \Drupal::configFactory()->getEditable('node_rollcall.settings')->set("auth_token", "abc")->save();

    if ($apiKey !== $token || $apiKey == NULL) {
      return new CacheableJsonResponse([
        'status' => 'error',
        'response' => 'Could not authenticate',
      ], 401);
    }

    return new CacheableJsonResponse([], 200, [], false);

  }

  private function getPayload(): array|bool {

    // Get the content of the API request.
    $payload = $this->request->getCurrentRequest()->getContent();

    // Make an object out of the expected JSON string.
    try {
      $payload = json_decode($payload);
      if (empty($payload)) {
        throw new \Exception();
      }
    } catch (\Exception $e) {
      $this->response = new CacheableJsonResponse([
        'status' => 'error',
        'response' => "Empty payload, or JSON error in payload",
      ], 400);
      return FALSE;
    }

    // If the content is a fileref, then load the file
    if (!empty($payload->file)) {
      if (file_exists($payload->file)) {
        $payload = file_get_contents($payload->file);
        try {
          $payload = json_decode($payload);
          if (empty($payload)) {
            throw new \Exception();
          }
        } catch (\Exception $e) {
          $this->response =  new CacheableJsonResponse([
            'status' => 'error',
            'response' => "Empty file, or JSON error in file",
          ], 400);
          return FALSE;
        }
      }
      else {
        $this->response = new CacheableJsonResponse([
          'status' => 'error',
          'response' => "File cannot be found, or permissions block loading.",
        ], 400);
        return FALSE;
      }
    }

    // Return the payload.
    return $payload;

  }

  private function createDocket($docket): EntityInterface|bool {

    $this->stats["dockets"]["count"]++;

    if ($node = $this->createNode()) {

      $node->setTitle($docket["docket"])
        ->set("body", $docket["subject"])
        ->set("status", 1)
        ->set("field_meeting_date", $docket["votedate"])  // should be string
        ->set("field_components", [])   // Reset votes set during create.
        ->save();

      return $node;

    }

    $this->response = new CacheableJsonResponse([
      'status' => 'error',
      'response' => "Could not create new Docket {$docket["docket"]}",
    ], 400);
    return FALSE;

  }

  private function createVote($vote): bool {

    self::fixVote($vote);
    $new_para = FALSE;

    $this->stats["rollcall_votes"]["count"]++;

    // Get or create the councillor taxonomy term entity.
    if ($councillor_tid = $this->councillors[$vote["councillor"]]) {
      $councillor_term = Term::load($councillor_tid["tid"]);
    }
    else {

      // Need to create this councillor.
      $councillor_term = Term::create([
        "vid" => "vocab_city_councillors",
        "status" => 0,                  // so we dont have conflicting councillors
        "name" => $vote["councillor"]
      ]);
      $councillor_term->set("field_district", NULL);
      $councillor_term->set("field_first_name", " ");
      $councillor_term->save();
      $this->stats["councillors"]["created"]++;

      // Add to cache.
      $this->councillors[$vote["councillor"]] = ["tid" => $councillor_term->id()];

      $councillor_tid = $this->councillors[$vote["councillor"]];

    }

    // Get the vote taxonomy term entity.
    if ($vote_tid = $this->votes[$vote["vote"]]) {
      $vote_term = Term::load($vote_tid["tid"]);
    }
    else {
      // We can't create votes that don't exist.
      $vote_term = NULL;
    }

    // Get or create the paragraph for this vote
    if (!$para = $this->loadParagraph($this->active_docket->id(), $councillor_tid["tid"])) {

      // There is no existing paragraph for this councillor for this docket
      $para = $this->createParagraph();
      $para->field_councillor->appendItem($councillor_term);
      if (!empty($vote_term)) {
        $para->field_vote->appendItem($vote_term);
      }
      $new_para = TRUE;

    }
    elseif (!empty($vote_term) && $para->field_vote->target_id != $vote_tid["tid"]) {

      // Paragraph already exists with a vote for this councillor but the vote
      // has changed, so update it.
      $para->field_vote = [];
      $para->field_vote->appendItem($vote_term);
      $this->stats["rollcall_votes"]["updated"]++;

    }

    // Save the paragraph.
    try {
      $para->save();
    }
    catch (\Exception $e) {
      $txt = $new_para ? "create new" : "update";
      $this->response = new CacheableJsonResponse([
        'status' => 'error',
        'response' => "Could not {$txt} vote record for {$vote->councillor} on docket {$vote->docket}",
      ], 400);
      return FALSE;
    }

    if ($new_para) {
      // Append the new paragraph to the node (Rollcall Docket)
      $this->active_docket->field_components->appendItem($para);
      $this->active_docket_changed = TRUE;
    }

    $vote_term = NULL;
    $councillor_term = NULL;
    $para = NULL;

    return TRUE;
  }

  private function saveDocket(): void {
    $this->active_docket->save();
    $this->active_docket->set("moderation_state", "published");
    $this->active_docket->save();
    $this->active_docket_changed = FALSE;
  }

  private function saveDocketIfChanged(): void {
    if (!empty($this->active_docket) && $this->active_docket_changed) {
      $this->saveDocket();
    }
  }

  private function loadParagraph(int $docketid, int $councillorid): EntityInterface|bool {
    if ($para = \Drupal::entityTypeManager()
      ->getStorage("paragraph")
      ->loadByProperties([
        "type" => "roll_call_vote",
        "parent_id" => $docketid,                // roll_call_docket ID
        "field_councillor" => $councillorid,     // Councillor TID
      ])) {
      return reset($para);
    }
    return FALSE;
  }

  private function createParagraph(): EntityInterface {
    $this->stats["rollcall_votes"]["created"]++;
    return \Drupal::entityTypeManager()
      ->getStorage("paragraph")
      ->create([
        "type" => "roll_call_vote",
      ]);
  }

  private function loadNode(int $id): EntityInterface {
    $this->stats["dockets"]["count"]++;
    return \Drupal::entityTypeManager()
      ->getStorage("node")
      ->load($id);
  }

  private function createNode(): EntityInterface {

    $this->stats["dockets"]["created"]++;

    return \Drupal::entityTypeManager()
      ->getStorage("node")
      ->create([
        "type" => "roll_call_dockets",
      ]);
  }

  private static function fixVote(array &$vote): array {
    // Maps legacy votes to new fuller description.
    $vote_map = [
      "n" => "No",
      "y" => "Yes",
      "np" => "Not Present",
      "p" => "Present",
      "no" => "No",
      "yes" => "Yes",
      "not present" => "Not Present",
      "notpresent" => "Not Present",
      "present" => "Present",
    ];
    $vote["vote"] = $vote_map[trim($vote["vote"], " \t\n\r\0\x0B.")];
    return $vote;

  }

}
