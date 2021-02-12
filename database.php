<?php

/**
* 
*/
class Database
{
	
	private $servername = "";
	private $username = "ocassioh_bbc";
	private $password = "m0vefast";
	private $database = "ocassioh_subbot";

	public $conn = "";

	function __construct()
	{
		echo "Connecting... ";
		try {
		    $this->conn = new PDO("mysql:host=$this->servername;dbname=$this->database", $this->username, $this->password);
		    // set the PDO error mode to exception
		    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		    echo "Connected successfully";
		    }
		catch(PDOException $e)
		    {
		    echo "Connection failed: " . $e->getMessage();
		    }
	}

	public function CreateTable()
	{
		$sql = "CREATE TABLE IF NOT EXISTS users (
		     id INT( 11 ) AUTO_INCREMENT PRIMARY KEY,
		     user_id INT(255) NOT NULL, 
		     keywords TEXT DEFAULT NULL,
		     channels TEXT DEFAULT NULL)";
		    // $this->conn->prepare("$sql");
		    // $sql->execute();
		    $this->conn->exec($sql);

		    echo "Created table";
	}

	public function NewKeyword($user_id, $keyword, $channel)
	{
		$sql = 'INSERT INTO users (user_id, keyword, channel) VALUES ('.$user_id.', "'.$keyword.'", "'.$channel.'")';
		// die($sql);
		$this->conn->exec($sql);
	}

	public function GetState($sender)
	{
		$stmt = $this->conn->prepare('SELECT `state` FROM state WHERE user_id = '.$sender);
		$stmt->execute();
		$state = $stmt->fetch();

		if (empty($state)) {
			$sql = 'INSERT INTO state (`user_id`, `state`) VALUES ('.$sender.', 1)';

			$this->conn->exec($sql);

			$state = 1;
		}

		return $state;
	}

	public function SetState($sender, $state)
	{
		$sql = 'UPDATE `state` SET `state` = '.$state.' WHERE `user_id` = '.$sender;
		// die($sql);
		$this->conn->exec($sql);

		echo " State Changed to: ".$state." ";
	}

	public function GetNewKeywords()
	{
		$stmt = $this->conn->prepare('SELECT * FROM `notifications` WHERE sent=0');

		$stmt->execute();
		$state = $stmt->fetchAll();
		return $state;

	}

	public function SetAsSent($notificationId)
	{
		$sql = 'UPDATE `notifications` SET `sent` = 1 WHERE id = '.$notificationId;
		$this->conn->exec($sql);
	}

	public function GetLastKeyword($sender)
	{
		$stmt = $this->conn->prepare('SELECT `keyword` FROM `users` WHERE `user_id`='.$sender.' ORDER BY `id` DESC');
		// echo 'SELECT `keyword` FROM `users` WHERE `user_id`='.$sender.'ORDER BY `id` DESC';
		$stmt->execute();
		$state = $stmt->fetchAll();
		print_r($state);
		return $state[0][0];
	}
}

?>