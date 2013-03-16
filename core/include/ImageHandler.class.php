<?php

/*
	Sample:
		$image =new ImageHandler($img_file);
		$image->squeeze(100,50);
		// $image->square();
		$image->save($cache_file);
*/

//-------------------------------------
// GDを使用して画像を変形する
class ImageHandler {
	
	// 元データ
	public $filename;
	public $type;
	public $width_raw;
	public $height_raw;
	
	// 出力画像サイズ
	public $top;
	public $left;
	public $width;
	public $height;
	
	// 切り出し画像サイズ
	public $crop_top;
	public $crop_left;
	public $crop_width;
	public $crop_height;
	
	// 読み込める画像フォーマット
	public $valid_type_list =array(
		IMAGETYPE_GIF =>"gif",
		IMAGETYPE_JPEG =>"jpeg",
		IMAGETYPE_PNG =>"png",
	);
		
	//-------------------------------------
	// 初期化
	public function __construct ($filename=null) {
		
		if ($filename) {
		
			$this->load($filename);
		}
	}
		
	//-------------------------------------
	// 対象ファイルの有効性チェック
	public function get_type () {
		
		return $this->type;
	}
	
	//-------------------------------------
	// 画像ファイルの読み込み
	public function load ($filename) {
		
		$this->filename =$filename;
		
		if (file_exists($filename) && is_file($filename)) {
			
			list($this->width_raw, $this->height_raw, $type) =@getimagesize($filename);
			
			$this->top =0;
			$this->left =0;
			$this->width =$this->width_raw;
			$this->height =$this->height_raw;
			
			$this->crop_top =0;
			$this->crop_left =0;
			$this->crop_width =$this->width_raw;
			$this->crop_height =$this->height_raw;
			
			$this->type =$this->valid_type_list[$type];
		}
	}
	
	//-------------------------------------
	// 長辺に合わせて指定サイズ以内に縮小
	public function squeeze ($width_limit=0, $height_limit=0) {
		
		$width =$this->width;
		$height =$this->height;
			
		if ($width_limit && $this->width > $width_limit) {
			
			$this->width =$width_limit;
			$this->height =$this->width*$height/$width;
		}
		
		if ($height_limit && $this->height > $height_limit) {
			
			$this->height =$height_limit;
			$this->width =$this->height*$width/$height;
		}
		
		$this->width =round($this->width);
		$this->height =round($this->height);
	}
	
	//-------------------------------------
	// 短辺に併せて中心を正方形にトリム
	public function square ($size=null) {

		if ($this->crop_width > $this->crop_height) {
		
			$this->crop_top =round(($this->crop_width-$this->crop_height)/2);
			$this->crop_width =$this->crop_height;
		}

		if ($this->crop_height > $this->crop_width) {
		
			$this->crop_left =round(($this->crop_height-$this->crop_width)/2);
			$this->crop_height =$this->crop_width;
		}
		
		if ($size!==null) {
		
			$this->height =$this->width =$size;
		
		} elseif ($this->height > $this->width) {
			
			$this->height =$this->width;
			
		} elseif ($this->height < $this->width) {
		
			$this->width =$this->height;
		}
	}
	
	//-------------------------------------
	// 中心から指定した幅、高さでトリム
	public function trim_center ($width=0, $height=0) {

		if ($width) {
			
			$this->crop_left =round($this->crop_width/2-$width/2);
			$this->crop_width =$width;
			$this->width =$width;
		}

		if ($height) {
		
			$this->crop_top =round($this->crop_height/2-$height/2);
			$this->crop_height =$height;
			$this->height =$height;
		}
	}
	
	//-------------------------------------
	// 画像を出力
	public function save ($dst_filename=null) {
		
		// 元画像ハンドラの作成
		$src_image =null;
		
		if($this->type == 'jpeg') { $src_image =imagecreatefromjpeg($this->filename); }
		if($this->type == 'png') { $src_image =imagecreatefrompng($this->filename); }
		if($this->type == 'gif') { $src_image =imagecreatefromgif($this->filename); }
		
		// 新しい画像ハンドラの作成
		$dst_image =imagecreatetruecolor($this->width,$this->height);
		
		// 透過色の保護
		if ($this->type == 'gif') {
		
			$alpha =imagecolortransparent($src_image);
			imagefill($dst_image, 0, 0, $alpha);
			imagecolortransparent($dst_image, $alpha);
		
		} elseif ($this->type == 'png') {
		
			imagealphablending($dst_image, false);
			imagesavealpha($dst_image, true);
		}
		
		// 変形させてコピー
		imagecopyresampled(
				$dst_image, $src_image,
				$this->top, $this->left, $this->crop_top, $this->crop_left, 
				$this->width, $this->height, $this->crop_width, $this->crop_height);
				
		// ファイルに保存
		if($this->type == 'jpeg') { imagejpeg($dst_image,$dst_filename); }
		if($this->type == 'png') { imagepng($dst_image,$dst_filename); }
		if($this->type == 'gif') { imagegif($dst_image,$dst_filename); }
		
		// ハンドラ開放
		imagedestroy($src_image); 
		imagedestroy($dst_image);
	}
	
	//-------------------------------------
	// 1x1のダミー画像を生成
	public function create_dummy_image ($color=255, $filename=null) {
		
		$image =imagecreatetruecolor(1, 1);
		$lcolor =imagecolorallocate($image, $color, $color, $color);
		imagesetpixel($image,0,0,$lcolor);
		imagepng($image);
		imagedestroy($image,$filename);
	}
}