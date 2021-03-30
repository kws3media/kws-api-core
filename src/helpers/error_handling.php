<?php

namespace Kws3\ApiCore\Error;

function tryDBGEnd($msg = null, $trace = null)
{
  try {
    if (\Kws3\ApiCore\Framework::isClockworkEnabled()) {
      if ($msg) {
        dbg()->error($msg);
      }
      if ($trace) {
        $splitted = parseDBGTrace($trace);
        if (count($splitted) > 0) {
          $c = dbg()->userData("ErrBkdn")->title("Error Breakdown");
          $c->table("Error Breakdown", $splitted);
        } else {
          dbg()->error($trace);
        }
      }
      dbg()->requestProcessed();
    }
  } catch (\Exception $ex) {
  }
}

function parseDBGTrace($contents)
{
  $items = preg_split("/(\[[^]]+\])/", $contents, -1, PREG_SPLIT_DELIM_CAPTURE);
  $res = [];
  for ($i = 1; $i < count($items); $i++) {
    if (preg_match("/\[([^\]]*)\]/", $items[$i], $pathWithLineNumber)) {
      list($path, $line_number) = explode(':', $pathWithLineNumber[1]);
      array_push($res, [
        'File:Line' => implode('/', array_slice(preg_split('/[\\/]/', $path, -1), -2, 2)) . ':' . $line_number,
        'Message' => $items[$i + 1]
      ]);
      $i++;
    }
  }
  return $res;
}


/**
 * Generic function to return 500 http response
 *
 * We use this function to respond to PHP errors and exceptions out of \Verified
 */
function send500Response()
{
  // We only support JSON/XML responses yet
  $json_500_response = '{
      "_meta":{
          "status":"ERROR",
          "count":1
      },
      "records":{
          "errorCode":500,
          "userMessage":"",
          "devMessage":"Catch-all",
          "more":"",
          "applicationCode":500
      }
  }';

  $xml_500_response = '<?xml version="1.0"?>
  <response>
      <_meta>
          <status>SUCCESS</status>
          <count>6</count>
      </_meta>
      <records>
          <errorCode>500</errorCode>
          <userMessage></userMessage>
          <devMessage></devMessage>
          <more></more>
          <applicationCode>500</applicationCode>
      </records>
  </response>';


  // Clean any previous output in the buffer
  while (ob_get_level())
    ob_end_clean();
  // Send 500 HTTP header
  header('HTTP/1.1 500 Internal Server Error');

  header('Status: 500 Internal Server Error');
  // Access control header
  header('Access-Control-Allow-Origin: *');

  tryDBGEnd();

  // Send 500 HTTP body
  // Default to JSON format
  if (!isset($_SERVER['HTTP_ACCEPT']) || $_SERVER["HTTP_ACCEPT"] != "application/xml") {
    header('Content-type: application/json');
    $response = str_replace(array(" ", "\n"), "", $json_500_response);
  } else {
    header('Content-type: application/xml');
    $response = str_replace(array(" ", "\n"), "", $xml_500_response);
  }
  echo $response;
}

// This function is called before the PHP engine is shut down
// We need it to handle all kind of errors
register_shutdown_function(function () {
  if (error_get_last()) {
    send500Response();
  }
});
