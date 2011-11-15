<?php

/*
サンプルコード
-------------------------------------
	// DB接続
	$wp =new WordPressAdapter(array(
		// "db_connection" =>mysql_connect...
		"db_name" =>"farmstone_wp",
		"db_user" =>"farmstone_wp",
		"db_password" =>"farmStone",
		"db_host" =>"localhost",
	));
	
	// ブログ投稿取得
	$posts =$wp->get_posts(array(
		"blog_id" =>3,
		"meta_keys" =>array(
			"ranking_img",
		),
	));
*/

//-------------------------------------
// Wordpressへ接続しての操作
class WordPressAdapter {
	
	protected $con;
	
	//-------------------------------------
	// エラー発生
	protected function raise_error ($msg) {
		
		if (function_exists("report_error")) {
			
			report_error($msg);
		
		} else {
		
			print "Error: ".$msg;
			exit;
		}
	}
	
	//-------------------------------------
	// 初期化
	public function WordPressAdapter ($options=array()) {
		
		if ($options["db_connection"]) {
		
			$this->con =$options["db_connection"];
			
		} else {
		
			$this->con =mysql_connect(
					$options["db_host"],
					$options["db_user"],
					$options["db_password"]);
			
			if ( ! $this->con) { 
				
				$this->raise_error('データベースに接続できませんでした');
			}
			
			mysql_query('SET NAMES utf8',$this->con);
			
			$result =mysql_select_db($options["db_name"], $this->con);
			
			if ( ! $result) { 
				
				$this->raise_error('データベースを選択できませんでした。');
			}
		}
	}
	
	//-------------------------------------
	// ブログ記事に関するデータの取得
	public function get_posts ($options=array()) {
		
		$post_table_prefix =strlen($options["blog_id"])
				? "wp_".$options["blog_id"]
				: "wp";
		$meta_keys =(array)$options["meta_keys"];
		$meta_keys_where_statement ="";
		
		foreach ($meta_keys as $meta_key) {
			
			$meta_keys_where_statement .=' meta_key = "'.$meta_key.'" OR ';
		}
		
		// メタデータの構築
		$metadata =array();
		$attached =array();
		
		// メタ情報の取得
		$result = mysql_query('
			SELECT * 
			FROM '.$post_table_prefix.'_postmeta 
			WHERE 
				'.$meta_keys_where_statement.'
				meta_key = "_wp_attachment_metadata" OR
				meta_key = "_wp_attached_file"
		',$this->con);
		
		while ($data = mysql_fetch_array($result,MYSQL_ASSOC)) {
		
			$metadata[$data['post_id']][$data['meta_key']] =$data['meta_value'];
			
			// 添付ファイルの参照を記録
			if ($data['meta_key'] == "_wp_attachment_metadata") {
			
				$attached_meta[$data['post_id']] =$data['meta_value'];
			}
			
			if ($data['meta_key'] == "_wp_attached_file") {
			
				$attached[$data['post_id']] =$data['meta_value'];
			}
		}
		
		$termsdata =array();
		$terms_name_data =array();
		$taxonomydata =array();
		$taxonomy_name_data =array();
		
		// Taxonomy情報の取得
		$result = mysql_query('
			SELECT 
				object_id,
				taxonomy,
				name
			FROM '.$post_table_prefix.'_term_relationships AS TR
			JOIN '.$post_table_prefix.'_term_taxonomy AS TT
			JOIN '.$post_table_prefix.'_terms AS T
			WHERE TR.term_taxonomy_id = TT.term_taxonomy_id
			AND TT.term_id = T.term_id
		',$this->con);
		
		while ($data = mysql_fetch_array($result,MYSQL_ASSOC)) {
		
			$termsdata[$data['object_id']][$data['taxonomy']][] =$data['name'];
		}

		// ポスト情報の構築
		$postdata =array();
		
		// ポスト情報の取得
		$result =mysql_query('
			SELECT * 
			FROM '.$post_table_prefix.'_posts
			WHERE 
				post_status != "trash" AND
				post_status != "future" AND
				post_status != "inherit" AND
				post_status != "draft" AND
				post_status != "auto-draft" AND
				post_type != "page"
		',$this->con);
		
		while ($data = mysql_fetch_array($result,MYSQL_ASSOC)) {
			
			foreach ($meta_keys as $meta_key) {
			
				if (isset($metadata[$data['ID']][$meta_key])
						&& $attached_meta[$metadata[$data['ID']][$meta_key]]) {
				
					$metadata[$data['ID']]["FileMeta_".$meta_key] 
							=$attached_meta[$metadata[$data['ID']][$meta_key]];
				}
				
				if (isset($metadata[$data['ID']][$meta_key])
						&& $attached[$metadata[$data['ID']][$meta_key]]) {
				
					$metadata[$data['ID']]["File_".$meta_key] 
							=$attached[$metadata[$data['ID']][$meta_key]];
				}
			}

			$key =$post_table_prefix."_".$data['ID'];
			$postdata[$key] =$data;
			$postdata[$key]["BID"]=$post_table;
			$postdata[$key]["meta"] =$metadata[$data['ID']];
			$postdata[$key]["term"] =$termsdata[$data['ID']];
		}
		
		return $postdata;
	}
	
	//-------------------------------------
	// データの併合
	public function merge_posts ($posts_list) {
	
		$merged =array();
		
		foreach ($posts_list as $posts) {
		
			$merged =array_merge($merged,$posts);
		}
		
		usort($merged,array($this,"sort_by_date"));
		
		return $merged;
	}
	
	//-------------------------------------
	// データの整列ハンドル
	public function sort_by_date ($a, $b) {
	
		if (strtotime($a["post_date"]) > strtotime($b["post_date"])) { 
			
			return -1;
			
		} elseif (strtotime($a["post_date"]) == strtotime($b["post_date"])) {
		
			return 0;
			
		} else {
		
			return +1;
		}
	}
}