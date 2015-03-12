<!DOCTYPE html>
<html>
  <head>
    <title>Timelinator</title>
    <link rel="stylesheet" type="text/css" href="static/style.css" />
  </head>

  <body>
    <h2>Welcome to Timelinator - The killer timeline app :-)</h2>
    <form action="timeline" method="post">
      <p><label for="status"> What's up? </label> <input type="text" id="status" name="status" /></p>
      <p><label for="person"> Who are you? </label> <input type="text" id="person" name="person" /></p>
      <input value="Submit" type="submit"/>
    </form>
  </body>

</html> 

<?php

require_once 'config.php';
require_once 'DatastoreService.php';
require_once 'google-api-php-client/src/Google/Client.php';
require_once 'google-api-php-client/src/Google/Auth/AssertionCredentials.php';
require_once 'google-api-php-client/src/Google/Service/Datastore.php';

DatastoreService::setInstance(new DatastoreService($google_api_config));

// Misc Query functions =============

function createQuery($kind_name) {
  $query = new Google_Service_Datastore_Query();
  $kind = new Google_Service_Datastore_KindExpression();
  $kind->setName($kind_name);
  $query->setKinds([$kind]);
  return $query;
}

function executeQuery($query) {
  $req = new Google_Service_Datastore_RunQueryRequest();
  $req->setQuery($query);
  $response = DatastoreService::getInstance()->runQuery($req);

  if (isset($response['batch']['entityResults'])) {
    return $response['batch']['entityResults'];
  } else {
    return [];
  }
}

function extractQueryResults($results) {
  $query_results = [];
  foreach($results as $result) {
    $id = @$result['entity']['key']['path'][0]['id'];
    $key_name = @$result['entity']['key']['path'][0]['name'];
    $props = $result['entity']['properties'];
    $status = $props['status']->getStringValue();
    $person = $props['person']->getStringValue();
    $timeline_item = [$person, $status];
    $query_results[] = $timeline_item;
  }
  return $query_results;
}
//Misc Query functions END =============

function get_status () {
  $timeline_items = extractQueryResults(executeQuery(createQuery('timeline_items')));
  foreach ($timeline_items as $timeline_item) {
    echo '<p><span id="person">' . $timeline_item[0] . '</span> said: <span id="status">' . $timeline_item[1] . '</span></p>';
  }
}

get_status();

//Safe data to the datastore. Runs only if form is filled

if ((!empty($_POST['status'])) && (!empty($_POST['person']))) {

  $status = $_POST['status'];
  $person = $_POST['person'];

  // Function creates entity and commits it to Datastore

  function safe_status($person, $status){
    // $timeline_item is an object containing all the information for a particular post (person's name, status and date) on the timeline
    $timeline_item = new Google_Service_Datastore_Entity();

    // create property to store $status
    $status_prop = new Google_Service_Datastore_Property();
    $status_prop->setStringValue($status);

    // create property to store $person
    $person_prop = new Google_Service_Datastore_Property();
    $person_prop->setStringValue($person);

    $timeline_item_properties = [];
    $timeline_item_properties["status"] = $status_prop;
    $timeline_item_properties["person"] = $person_prop;
    $timeline_item->setProperties($timeline_item_properties);

    // Assigning the KIND for the $timeline_item

    $path = new Google_Service_Datastore_KeyPathElement();
    $path->setKind("timeline_items");

    $key = new Google_Service_Datastore_Key();
    $key->setPath([$path]);

    $timeline_item->setKey($key);

    $mutation = new Google_Service_Datastore_Mutation();
    $mutation->setInsertAutoId([$timeline_item]);

    $req = new Google_Service_Datastore_CommitRequest();
    $req->setMode('NON_TRANSACTIONAL');
    $req->setMutation($mutation);

    return $req;
  }

  $req = safe_status($person, $status);

    try {
    // Commiting the the timeline status and posters name to the Google Datastore
    DatastoreService::getInstance()->commit($req);
  }
  catch (Google_Exception $ex) {
    syslog(LOG_WARNING, 'Commit to Cloud Datastore exception: ' . $ex->getMessage());
    echo "There was an issue -- check the logs.";
    return;
  }
  echo "Status saved. Refresh page to see it";
}
?>