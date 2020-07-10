<?php
// Based on Xero developer document from
/ /https://developer.xero.com/documentation/webhooks/overview
// Returning data in the response body to Xero will cause the webhook
// verification to fail, to get around this for testing store all the 
//information we needed into a text file to helps us debug any issues.

// ----------------------------------------------------------------------------

// The payload in webhook MUST be read as raw data format,
// even thought the webhook is sent with the
// Content-Type header with 'application/json'.
//
// Otherwise any preprocess payload could be lead to incorrectly
// computed signature key.

// Get payload
$rawPayload = file_get_contents('php://input');

// ------------------------------------
// Compute hashed signature key with our webhook key

// Update your webhooks key here
$webhookKey = '--YOUR_WEBHOOK_KEY---';

// Compute the payload with HMACSHA256 with base64 encoding
$computedSignatureKey = base64_encode(
  hash_hmac('sha256', $rawPayload, $webhookKey, true)
);

// Signature key from Xero request
$xeroSignatureKey = $_SERVER['HTTP_X_XERO_SIGNATURE'];

// Response HTTP status code when:
//   200: Correctly signed payload
//   401: Incorrectly signed payload
$isEqual = false;
if (hash_equals($computedSignatureKey, $xeroSignatureKey)) {
  $isEqual = true;
  http_response_code(200);
} else {
  http_response_code(401);
}

// ------------------------------------
// Store information into file
// IMPORTANT - if you need to set permissions to allow
// the file to be created - read more in the blog post.
//https://devblog.xero.com/lets-play-web-hooky-with-php-34a141dcac0a

// Request Headers
$filedata = sprintf(
  "%s %s %s\n\n---- Request headers ----\n",
  $_SERVER['REQUEST_METHOD'],
  $_SERVER['REQUEST_URI'],
  $_SERVER['SERVER_PROTOCOL']
);
foreach (getHeaderList() as $name => $value) {
  $filedata .= $name . ': ' . $value . "\n";
}

// Request Body
$filedata .= "\n---- Request body ----\n";
$filedata .= $rawPayload . "\n";

// Signature key
$filedata .= "\n---- Signature key ----";

$filedata .= "\nComputed signature key:\n";
$filedata .= $computedSignatureKey;

$filedata .= "\nXero signature key:\n";
$filedata .= $xeroSignatureKey;

// Result
$filedata .= "\n\n---- Result ----\n";
if ($isEqual) {
  $filedata .= "Match";
} else {
  $filedata .= "Not match";
}

// Store to file
$currentTime = microtime();
$filename = substr($currentTime, 11) . substr($currentTime, 2, 8);

//output to file
//file_put_contents('./'.$filename.'.txt', $filedata);

// ----------------------------------------------------------------------------
// Helper function(s)

function getHeaderList() {
  $headerList = [];
  foreach ($_SERVER as $name => $value) {
    if (preg_match('/^HTTP_/',$name)) {
      $headerList[$name] = $value;
    }
  }
  return $headerList;
}

// ----------------------------------------------------------------------------
?>
