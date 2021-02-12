<?php
require_once('database.php');

/**
* 
*/
class Beebsbot
{
	private $database;
	private $access_token;
	private $verify_token;
	private $hub_verify_token = null;
	private $input;
	private $sender;
	private $recipient;
	private $message;
	private $message_to_reply;
	private $postback;

	function __construct($settings, $input, $sender, $recipient, $message, $postback)
	{
		$this->database = new Database();
		$this->access_token = $settings["access_token"];
		$this->verify_token = $settings["verify_token"];
		$this->input 		= $input;
		$this->sender 		= $sender;
		$this->recipient 	= $recipient;
		$this->message 		= $message;
		$this->postback 	= $postback;
	}

	/**
	 * Some Basic rules to validate incoming messages
	 */
	public function getReply($message)
	{
		$channels = "News1";
		// die(var_dump($this->postback));
		echo " Postback: ".$this->postback." | ";
		$state = $this->database->GetState($this->sender);
		// die("* ".$state['state']." *");

		if (preg_match('[hello]', strtolower($this->message))) {

			$message_to_reply = '{"text" : "Enter a keyword:"}';

			$text1 = "Oh hello there! I monitor live television for you and push you notifications when somebody starts talking about something you're interested in.";
			$text2 = "If you give me a list of keywords/phrases to look for, I'll send you a message when somebody says that phrase live on air.";
			$text3 = 'That could be something like "Donald Trump" or "train crash". To start, give me a phrase to listen for.';
			
			$this->sendMessage($text1);
			$this->sendMessage($text2);
			$this->sendMessage($text3);

			$this->database->SetState($this->sender, 1);

		}elseif ($this->postback == "Finished") {
			
			$this->database->SetState($this->sender, 0);

			$text = "Great, that's it! I'll sit here and watch TV (it's a hard life) and I'll immediately send you a message if anybody says any of those phrases.";

			$message_to_reply = '{"text" : "'.$text.'"}';

		}elseif ($state['state'] == 1) {
			
			$this->database->NewKeyword($this->sender, $message, $channels);
			$lastKeyword = $this->database->GetLastKeyword($this->sender);
			$text1 = "Thanks, I'll now be monitoring TV broadcasts for '".$lastKeyword."' .";
			$this->sendMessage($text1);

			$text2 = "Either send another phrase, or click 'Finished'.";

			$message_to_reply = '
			{
				"attachment":{
				  "type":"template",
				  "payload":{
					"template_type":"button",
					"text":"'.$text2.'",
					"buttons":[
					  {
						"type":"postback",
						"title":"Finished",
						"payload":"Finished"
					  }
					]
				  }
				}
			}';
		}

		return $message_to_reply;
	}

	// SEND REPLY
	public function sendReply()
	{		
		$message_to_reply = $this->getReply($this->message);

		//API Url
		$url = 'https://graph.facebook.com/v2.6/me/messages?access_token='.$this->access_token;

		//Initiate cURL.
		$ch = curl_init($url);

		//The JSON data.
		$jsonData = '{
			"recipient":{
				"id":'.$this->sender.'
			  },
			  "message":'.$message_to_reply.'
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

	public function sendMessage($text)
	{
		//API Url
		$url = 'https://graph.facebook.com/v2.6/me/messages?access_token='.$this->access_token;

		//Initiate cURL.
		$ch = curl_init($url);

		//The JSON data.
		$jsonData = '{
			"recipient":{
				"id":'.$this->sender.'
			  },
			"message":{
			      "text":"'.$text.'"
			}
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
}



	