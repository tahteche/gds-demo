<?php

require_once 'google-api-php-client/src/Google/Client.php';
require_once 'google-api-php-client/src/Google/Auth/AssertionCredentials.php';
require_once 'google-api-php-client/src/Google/Service/Prediction.php';

final class PredictionService
{
  private static $instance = null;

  private static $required_options = [
    'dataset-id',
    'application-id',
  ];

  static $scopes = [
    "https://www.googleapis.com/auth/devstorage.read_only",
    "https://www.googleapis.com/auth/prediction",
  ];

  private $trainedmodels;

  private $appId;

  private $config = [];

  public function __construct(array $options)
  {
    $this->config = $options;
    $this->init($this->config);
  }

  private function init($options) {
    foreach(self::$required_options as $required_option) {
      if (!array_key_exists($required_option, $options)) {
        throw new InvalidArgumentException(
          'Option ' . $required_option . ' must be supplied.');
      }
    }
    $client = new Google_Client();
    $client->setApplicationName($options['application-id']);
    // 1.0-alpha version
    $client->setAssertionCredentials(new Google_Auth_AssertionCredentials(
      $options['service-account-name'],
      self::$scopes,
      $options['private-key']));
    $service = new Google_Service_Prediction($client);

    $this->trainedmodels = $service->trainedmodels;
    $this->appId = $options['dataset-id'];
  }

  public static function getInstance() {
    if (self::$instance == null) {
      throw new UnexpectedValueException('Instance has not been set.');
    }
    return self::$instance;
  }

  public static function setInstance($instance) {
    if (self::$instance != null) {
      throw new UnexpectedValueException('Instance has already been set.');
    }
    self::$instance = $instance;
  }

  public function predict(Google_Service_Prediction_Input $postBody, $trainedModelId, $optParams = [])
  {
    return $this->trainedmodels->predict($this->appId, $trainedModelId, $postBody, $optParams);
  }

  public static function toPredictionInput($input)
  {
    $inputInput = new Google_Service_Prediction_InputInput();
    $inputInput->setCsvInstance(array($input));

    $ret = new Google_Service_Prediction_Input();
    $ret->setInput($inputInput);
    return $ret;
  }
}