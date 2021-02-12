<?php
require_once('database.php');
include 'settings.php';


$access_token = $settings["access_token"];
$verify_token = $settings["verify_token"];
$url = 'https://graph.facebook.com/v2.6/me/messages?access_token='.$access_token;
$database = new Database();

$notificationsToBeSent = $database->GetNewKeywords();


foreach ($notificationsToBeSent as $notification) {
    $helloMessage = getHelloMessage($notification["keyword"]);
    sendMessage($notification["user_id"], $helloMessage);
    $notificationMessage = getNotificationMessage($notification["context"], $notification["image"]);
    sendMessage($notification["user_id"], $notificationMessage);

    // mark as sent
    $database->SetAsSent($notification["id"]);
}

function sendMessage($userId, $message)
{
  global $url;
  //Initiate cURL.
  $ch = curl_init($url);

  //The JSON data.
  $jsonData = '{
      "recipient":{
          "id":'.$userId.'
        },
        "message":'.$message.'
  }';

  //Encode the array into JSON.
  $jsonDataEncoded = $jsonData;
  //Tell cURL that we want to send a POST request.
  curl_setopt($ch, CURLOPT_POST, 1);
  //Attach our encoded JSON string to the POST fields.
  curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
  //Set the content type to application/json
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
  //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

  //Execute the request
  $result = curl_exec($ch);

}

function getHelloMessage($keyword) {
  return '
					{
            "text":"Hello! I\'ve just heard someone mention '.$keyword.' on TV."
          }';
}

function getNotificationMessage($text, $imageUrl) {
  return '
					{
            "attachment":{
              "type":"template",
              "payload":{
                "template_type":"generic",
                "elements":[
                  {
                    "title":"BBC News",
                    "image_url":"'.$imageUrl.'",
                    "subtitle":"'.trim($text).'",
                    "buttons":[
                      {
                        "type":"web_url",
                        "url":"http://www.bbc.co.uk/iplayer/live/bbcnews",
                        "title":"Watch Live",
                        "webview_height_ratio": "full"
                      }
                    ]
                  }
                ]
              }
            }
          }';
}