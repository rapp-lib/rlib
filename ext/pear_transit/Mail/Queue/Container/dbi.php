<?php

	require_once('Mail/Queue/Container.php');

//-------------------------------------
// 
class Mail_Queue_Container_dbi extends Mail_Queue_Container {

	protected $mail_table;
	
	//-------------------------------------
	// 
	public function Mail_Queue_Container_dbi ($options) {
	
		$this->mail_table =array($options['mail_table'],"QM");
		$this->setOption($options);
	}
	
	//-------------------------------------
	// 
	public function _preload() {
	
		$query =array(
			"table" =>$this->mail_table,
			"conditions" =>array(
				"QM.sent_time" =>null,
				"QM.try_sent <" =>$this->try,
				"QM.time_to_send <=" =>date('Y-m-d H:i:s')
			),
			"order" =>"time_to_send",
			"offset" =>$this->offset,
			"limit" =>$this->limit,
		);
		$rows =dbi()->select($query);
		report($rows);
		$this->_last_item =0;
		$this->queue_data =array();
		
		foreach ($rows as $row) {
			
			$recipient =$this->_isSerialized($row['QM.recipient']) 
					? unserialize($row['QM.recipient']) 
					: $row['QM.recipient'];
			
			$this->queue_data[$this->_last_item] =new Mail_Queue_Body(
				$row['QM.id'],
				$row['QM.create_time'],
				$row['QM.time_to_send'],
				$row['QM.sent_time'],
				$row['QM.id_user'],
				$row['QM.ip'],
				$row['QM.sender'],
				$recipient,
				unserialize($row['QM.headers']),
				unserialize($row['QM.body']),
				(boolean)$row["QM.delete_after_send"],
				$row['QM.try_sent']
			);
			
			$this->_last_item++;
		}

		return true;
	}
	
	//-------------------------------------
	// 
	public function put (
			$time_to_send, 
			$id_user, 
			$ip, 
			$sender,
			$recipient, 
			$headers, 
			$body, 
			$delete_after_send=true) {
		
		$query =array(
			"table" =>$this->mail_table,
			"fields" =>array(
				"COALESCE(MAX(id),0)+1 AS next_id",
			),
		);
		$row =dbi()->select_one($query);
		
		$query =array(
			"table" =>$this->mail_table,
			"fields" =>array(
				"QM.id" =>$row["next_id"],
				"QM.create_time" =>date('Y-m-d H:i:s'),
				"QM.time_to_send" =>(string)$time_to_send, 
				"QM.id_user" =>(string)$id_user, 
				"QM.ip" =>(string)$ip, 
				"QM.sender" =>(string)$sender,
				"QM.recipient" =>(string)$recipient, 
				"QM.headers" =>(string)$headers, 
				"QM.body" =>(string)$body, 
				"QM.delete_after_send" =>($delete_after_send ? 1 : 0),
			),
		);
		dbi()->insert($query);
		
		return dbi()->last_insert_id($this->mail_table,"id");
	}
	
	//-------------------------------------
	// 
	public function countSend ($mail) {
		
		$count =(int)$mail->_try();
		
		$query =array(
			"table" =>$this->mail_table,
			"fields" =>array(
				"QM.try_sent" =>$count,
			),
			"conditions" =>array(
				"QM.id" =>(string)$mail->getId(),
			),
		);
		dbi()->update($query);
	
		return $count;
	}
	
	//-------------------------------------
	// 
	public function setAsSent ($mail) {
		
		$query =array(
			"table" =>$this->mail_table,
			"fields" =>array(
				"QM.sent_time" =>date('Y-m-d H:i:s'),
			),
			"conditions" =>array(
				"QM.id" =>(string)$mail->getId(),
			),
		);
		dbi()->update($query);
		
		return true;
	}
	
	//-------------------------------------
	// 
	public function getMailById ($id) {
	
		$query =array(
			"table" =>$this->mail_table,
			"conditions" =>array(
				"QM.id" =>(string)$id,
			),
		);
		$row =dbi()->select_one($query);
		
		$recipient =$this->_isSerialized($row['QM.recipient']) 
				? unserialize($row['QM.recipient']) 
				: $row['QM.recipient'];
				
		$mail =new Mail_Queue_Body(
			$row['QM.id'],
			$row['QM.create_time'],
			$row['QM.time_to_send'],
			$row['QM.sent_time'],
			$row['QM.id_user'],
			$row['QM.ip'],
			$row['QM.sender'],
			$recipient,
			unserialize($row['QM.headers']),
			unserialize($row['QM.body']),
			(boolean)$row['QM.delete_after_send'],
			$row['QM.try_sent']
		);
		
		return $mail;
	}
	
	//-------------------------------------
	// 
	public function getQueueCount () {
	
		$query =array(
			"table" =>$this->mail_table,
		);
		$count =dbi()->select_count($query);
		
		return $count;
	}
	
	//-------------------------------------
	// 
	public function deleteMail ($id) {
	
		$query =array(
			"table" =>$this->mail_table,
			"conditions" =>array(
				"QM.id" =>(string)$id,
			),
		);
		dbi()->delete($query);
		
		return true;
	}
}
