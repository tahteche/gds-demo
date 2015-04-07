<?php
require_once 'config.php';
require_once 'PredictionService.php';
require_once 'google-api-php-client/src/Google/Client.php';
require_once 'google-api-php-client/src/Google/Auth/AssertionCredentials.php';
require_once 'google-api-php-client/src/Google/Service/Prediction.php';

PredictionService::setInstance(new PredictionService($google_api_config));

function predictLang ($input)
{
  $input = PredictionService::toPredictionInput($input);
  $trainedModelId = "lang-detect-model";
  $result = PredictionService::getInstance()->predict($input, $trainedModelId);
  $lang = $result->outputLabel;
  return $lang;
}