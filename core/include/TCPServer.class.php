<?php

//-------------------------------------
// 
class TCPServer {
	
	protected $host;
	protected $port;
	protected $handler;
	protected $sock_table;
	protected $server_sock;
	
	//-------------------------------------
	// 初期化
	public function __construct ($host, $port, $handler=null) {
	
		$this->host =$host;
		$this->port =$port;
		$this->handler =$handler
				? $handler
				: $this;
		$this->sock_table =array();
		$this->server_sock =null;
	}
	
	//-------------------------------------
	// 初期化
	public function __destruct () {
		
		if ($this->server_sock) {
			
			$this->end_loop();
		}
	}
	
	//-------------------------------------
	// 接続待ち受けループ開始
	public function start_loop () {
		
		ob_end_clean();
		
		$this->server_sock =socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		register_shutdown_function(array($this,"end_loop"));
		
		if ($this->server_sock === false) {
		
			$this->debug_state("ERROR","SERVER",
					"socket_create Failed...".socket_strerror(socket_last_error()));
			exit;
		}
	
		if (socket_bind($this->server_sock, $this->host, $this->port) === false){
		
			$this->debug_state("ERROR","SERVER",
					"socket_bind Failed (".$this->host.":".$this->port
					.")...".socket_strerror(socket_last_error()));
			exit;
		}
	
		if (socket_listen($this->server_sock, $backlog=5) === false) {
		
			$this->debug_state("ERROR","SERVER",
					"socket_listen Failed...".socket_strerror(socket_last_error()));
			exit;
		}
	
		if (socket_set_nonblock($this->server_sock) === false){
		
			$this->debug_state("ERROR","SERVER",
					"socket_set_nonblock Failed...".socket_strerror(socket_last_error()));
			exit;
		}
					
		$this->debug_state("START","SERVER",$this->host.":".$this->port);
		
		$this->handler->on_start();
		
		// Server/Clientソケットの配列
		$this->sock_table =array();
		$this->sock_table["SERVER"] =array();
		$this->sock_table["SERVER"]["id"] ="SERVER";
		$this->sock_table["SERVER"]["sock"] =$this->server_sock;

		while (true) {
			
			$read_socks =array();
			
			$this->handler->on_idle(null);
				
			foreach ($this->sock_table as $sock => $dummy) {
				
				$this->handler->on_idle($sock);
		
				$read_socks[] =$this->sock_table[$sock]["sock"];
			}

			// ソケットのSelect
			socket_select($read_socks,$write=null,$exception=null,5);
			
			// 変化のあったソケットの配列について処理
			foreach ($read_socks as $changed_sock) {
				
				// サーバソケットに変化＝新しい接続が来た
				if ($changed_sock == $this->server_sock) {
					
					$client_sock =socket_accept($this->server_sock);
					$this->sock_table[$client_sock] =array();
					$this->sock_table[$client_sock]["id"] =(int)$client_sock;
					$this->sock_table[$client_sock]["sock"] =$client_sock;
					
					$this->debug_state("NEW_CLIENT",$client_sock);
				
					$this->handler->on_connect($client_sock);
				
				// クライアントソケットからのデータ受信
				} else {
					
					$client_sock =$changed_sock;
					$buf =socket_read($client_sock, $bufsize=1024);
					
					// 読み込みが終わったらクローズしてソケット配列から削除
					if ($buf === "") {
					
						socket_close($client_sock);
						unset($this->sock_table[$client_sock]);
						
						$this->debug_state("CLOSE",$client_sock,$buf);
				
						$this->handler->on_close($client_sock);
					
					// データ受信
					} else {
						
						$this->debug_state("DATA_RECEIVED",$client_sock,$buf);
				
						$this->handler->on_data_received($client_sock,$buf);
					}
				}
			}
		}
	}
	
	//-------------------------------------
	// 接続待ち受けループ終了
	public function send_data ($id, $data) {
						
		$this->debug_state("SENT_DATA",$id,$data);
		
		socket_write($this->sock_table[$id]["sock"],$data);
	}
	
	//-------------------------------------
	// 切断
	public function close ($client_id) {
		
		$this->debug_state("CLOSE",$client_id);
				
		$this->handler->on_close($client_id);
		
		socket_close($this->sock_table[$client_id]["sock"]);
		unset($this->sock_table[$client_id]);
	}
	
	//-------------------------------------
	// 接続待ち受けループ終了
	public function end_loop () {
		
		foreach ($this->sock_table as $sock) {
		
			socket_close($sock["sock"]);
		}
		
		socket_close($this->server_sock);
			
		$this->debug_state("END","SERVER");
				
		$this->handler->on_end();
		
		$this->server_sock =null;
	}
	
	//-------------------------------------
	// デバッグ出力
	public function debug_state ($msg=null, $client_id=null, $data=null) {
	
		print "#";
		if ($msg !== null) { print($msg); }
		if ($client_id !== null) { print("(".((int)$client_id).")"); }
		if ($data !== null) { print(": ".trim($data).""); }
		print "\n";
	}
	
	//-------------------------------------
	// サーバの起動
	public function on_start () {
	}
	
	//-------------------------------------
	// サーバの終了
	public function on_end () {
	}
	
	//-------------------------------------
	// クライアントの接続
	public function on_connect ($client_id) {
	}
	
	//-------------------------------------
	// データの受信
	public function on_data_received ($client_id, $data) {
	}
	
	//-------------------------------------
	// サーバの待機
	public function on_idle ($client_id) {
	}
}