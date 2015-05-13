<?php

//===========Including Google Cloud Datastore API=====================
require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';
use google\appengine\api\cloud_storage\CloudStorageTools;

// Created GCS upload url first since it needs to be past to the view (timelineView.php)
$options = [ 'gs_bucket_name' => 'gds-demo.appspot.com' ];
$upload_url = CloudStorageTools::createUploadUrl('/timeline', $options);

if (isset($_POST['status'])) {
 header('Location: ' . $_SERVER['REQUEST_URI']);
}

require_once 'login.php';
require_once 'timelineView.php';
require_once 'prediction.php';
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

  //$sortProperty is property used to sort the results  
  $sortProperty = new Google_Service_Datastore_PropertyReference();
  $sortProperty->setName('time');

  //$sortOrder uses $sortProperty and a direction (DESCENDING/ASCENDING) to sort results
  $sortOrder = new Google_Service_Datastore_PropertyOrder();
  $sortOrder->setDirection('DESCENDING');
  $sortOrder->setProperty($sortProperty);

  $query->setKinds([$kind]);
  $query->setGroupBy($sortProperty);
  $query->setOrder([$sortOrder]);
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
    $lang = $props['lang']->getStringValue();
    if (isset($props['time'])) {
      $time = $props['time']->getDateTimeValue();
    }
    else{
      $time = null;
    }
    if (isset($props['picture'])) {
      $picture = $props['picture']->getStringValue();
    }
    else{
      $picture = null;
    }
    $timeline_item = [$person, $status, $time, $lang, $picture];
    $query_results[] = $timeline_item;
    unset($result);
  }
  return $query_results;
}
//Misc Query functions END =============

function get_status () {
  $timeline_items = extractQueryResults(executeQuery(createQuery('timeline_items')));
  $i = 1; //Initialising $i Used to create alternating colors of the timeline
  foreach ($timeline_items as $timeline_item) {
    if (($i % 2) == 0) {
      $rowParity = 'even';
    }
    else{
      $rowParity = 'odd';
    }    

    //Converting the time ($timeline_item) from RFC 3339 format to readable time.
    $time = new DateTime($timeline_item[2]);
    $readableTime = $time->format('H:i Y-m-d');

    //Create display URL for image and resize image to 400px
    if (!empty($timeline_item[4]) && ($timeline_item[4] != 'null')) {
      $timeline_item[4] = CloudStorageTools::getImageServingUrl($timeline_item[4],
                                            ['size' => 400]);
    }

    echo '<div class="post-item ' . $rowParity . '"><p class="status-info">At <span class="time">' . $readableTime .
         '</span>, <span class="person">' . $timeline_item[0] . '</span> said: </p><p class="status">' . $timeline_item[1] .
          ' (' . $timeline_item[3] . ')</p><img src="' . $timeline_item[4] . '" /></div>' . "\n";

    $i++;
  }
  echo "</div></body></html>"; //end of HTML page
}

//Safe data to the datastore. Runs only if form is filled

if (!empty($_POST['status'])) {

  $status = $_POST['status'];
  $person = $user->getNickname();

  //Safe uploaded file to GCS and return file name to be used by Datastore

  if (!empty($_FILES['picture']['tmp_name'])) {
    $gs_name = $_FILES['picture']['tmp_name'];
    $filename = time() . '.jpg';
    $newFileLocation = 'gs://gds-demo.appspot.com/' . $filename;
    move_uploaded_file($gs_name, $newFileLocation);
    $picture = $newFileLocation;
  } 
  else{
    $picture = 'null';
  }



  // Function creates entity and commits it to Datastore

  function safe_status($person, $status, $file){
    // $timeline_item is an object containing all the information for a particular post (person's name, status, picture and date) on the timeline
    $timeline_item = new Google_Service_Datastore_Entity();

    // create property to store $status
    $status_prop = new Google_Service_Datastore_Property();
    $status_prop->setStringValue($status);

    // create property to store $person
    $person_prop = new Google_Service_Datastore_Property();
    $person_prop->setStringValue($person);

    //create property to store $file
    $file_prop = new Google_Service_Datastore_Property();
    $file_prop->setStringValue($file);

    //save the time
    $time = new Google_Service_Datastore_Property();
    $time->setDateTimeValue(date(DATE_RFC3339));

    // predict the language of the status using predictLang() in prediction.php
    $lang_prop = new Google_Service_Datastore_Property();
    $lang = predictLang($status);
    $lang_prop->setStringValue($lang);

    $timeline_item_properties = [];
    $timeline_item_properties["status"] = $status_prop;
    $timeline_item_properties["person"] = $person_prop;
    $timeline_item_properties["picture"] = $file_prop;
    $timeline_item_properties["time"] = $time;
    $timeline_item_properties["lang"] = $lang_prop;
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

  $req = safe_status($person, $status, $picture);

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
get_status();
?>