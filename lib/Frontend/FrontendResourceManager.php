<?php
/**
 * @require functions/tag
 */
namespace R\Lib\Frontend;

/**
 * フロントエンドリソース管理機能
 */
class FrontendResourceManager
{
    private static $instance = null;

    private $modules = array();
    private $buffer = array();
    private $assets_urls = array();

    private static $state_ids = array(
        "before_html" => 0,
        "start_head" => 1,
        "end_head" => 2,
        "start_body" => 3,
        "end_body" => 4,
        "after_html" => 5,
    );
    private $state = 0;

    /**
     * FrontendResourceManagerのSingletonインスタンスを返す
     */
    public static function getInstance ()
    {
        if ( ! self::$instance) {
            self::$instance = new FrontendResourceManager();
        }
        return self::$instance;
    }

// -- アセット管理

    /**
     * アセットDIR/URLを登録する
     * アセットDIR以下の.assets.phpからモジュールカタログを読み込む
     */
    public function registerAssetsDirUrl ($dir, $url)
    {
        // ディレクトリがなければエラー
        if ( ! is_dir($dir)) {
            report_error("アセットディレクトリが不正です",array(
                "dir" => $dir,
                "url" => $url,
            ));
        }
        $dir = realpath($dir);
        // 登録済みであればエラー
        if ($this->assets_dirs[$dir]) {
            report_error("アセットディレクトリが登録済みです",array(
                "dir" => $dir,
                "url" => $url,
                "url_registered" => $this->assets_dirs[$dir],
            ));
        }
        // アセットカタログを読み込む
        $assets_catalog_php = $dir."/.assets.php";report($assets_catalog_php);
        if (file_exists($assets_catalog_php)) {
            $frontend = $this;
            include($assets_catalog_php);
        }
        $this->assets_dirs[$dir] = $url;
    }

// -- ステート管理

    /**
     * ステートの設定
     */
    public function setState ($state_id)
    {
        if ( ! isset(self::$state_ids[$state_id])) {
            report_error("ステートの指定が不正です",array(
                "state_id" => $state_id,
                "state_ids" => self::$state_ids,
            ));
        }
        $this->state = self::$state_ids[$state_id];
    }

    /**
     * ステートの確認
     */
    public function checkState ($state_id_start, $state_id_end)
    {
        if ( ! isset(self::$state_ids[$state_id_start]) ||  ! isset(self::$state_ids[$state_id_end])) {
            report_error("ステートの指定が不正です",array(
                "state_id_start" => $state_id_start,
                "state_id_end" => $state_id_end,
                "state_ids" => self::$state_ids,
            ));
        }
        return $this->state >= $state_id_start && $this->state <= $state_id_end;
    }

// -- バッファ制御

    /**
     * Buffer済みのResourceを作成
     */
    public function buffer ($data, $data_type, $buffer_name="default")
    {
        $resource = new Resource($this, $data, $data_type);
        $this->buffer[$buffer_name][] = $resource;
        return $resource;
    }

    /**
     * Bufferに登録されたデータを出力する
     * 出力時に依存関係を解決を行う
     * headを指定するとCSSコード/URLのみを読み込む
     */
    public function flush ($buffer_name="default")
    {
        // 全Bufferを出力する指定
        if ($buffer_name=="*") {
            $buffer_name = array_keys($this->buffer);
        }
        if ( ! is_array($buffer_name)) {
            $buffer_name = array($buffer_name);
        }
        // Buffer内のリソースを依存解決したコードにして取得
        $html = "";
        foreach ($buffer_name as $buffer_name_str) {
            foreach ((array)$this->buffer[$buffer_name_str] as $resource) {
                $html .= $resource->getHtmlWithDepenedencies();
            }
        }
        return $html;
    }

// -- モジュール依存管理

    /**
     * モジュールとして登録されたResourceを作成
     */
    public function register ($module_name, $version, $data, $data_type)
    {
        $resource = new Resource($this, $data, $data_type);
        $resource->setModuleName($module_name, $version);
        $this->modules[$module_name][$version] = $resource;
        return $resource;
    }

    /**
     * Moduleを読み込み登録済みとして記録
     */
    public function loaded ($module_name, $version)
    {
        $this->loaded_modules[$module_name] = $version;
    }

    /**
     * Moduleを読み込む
     */
    public function load ($module_name, $required_version)
    {
        // モジュールが読み込み済みの場合
        if ($version = $this->loaded_modules[$module_name]) {
            if ( ! $this->checkVersion($version, $required_version)) {
                report_error("読み込み済みモジュールが適合しません",array(
                    "module_name" => $module_name,
                    "loaded_version" => $version,
                    "required_version" => $required_version,
                    "loaded_modules" => $this->loaded_modules,
                ));
            }

            return "";
        }
        // 適合する最新版のモジュールを読み込む
        if (is_array($this->modules[$module_name])) {
            krsort($this->modules[$module_name]);
            foreach ((array)$this->modules[$module_name] as $version => $resource) {
                if ($this->checkVersion($version, $required_version)) {
                    // ロード済みとして記録してHTMLを返す
                    $this->loaded_modules[$module_name] = $version;
                    return $resource->getHtmlWithDepenedencies();
                }
            }
        }
        report_error("依存モジュールが読み込めませんでした",array(
            "module_name" => $module_name,
            "required_version" => $required_version,
            "modules" => $this->modules,
        ));
    }

    /**
     * 要求バージョン指定に適合するかチェック
     */
    private function checkVersion ($version, $required_version)
    {
        if ( ! is_numeric($required_version) && ! $required_version) {
            $required_version = "*";
        }
        // versionを1.2.3=>10203 のような数値に変換
        $v = 0;
        foreach (explode('.',$version) as $i => $p) {
            $v += $p*pow(100,2-$i);
        }
        // 要求Versionを1.2.*=>(10200-10299)のような範囲に変換
        $v_max = $v_min = 0;
        foreach (explode('.',$required_version) as $i => $p) {
            if (is_numeric($p)) {
                $v_max = $v_min += $p * pow(100, 2 - $i);
            }
            if ($p=="*") {
                $v_max = $v_min + pow(100, 2 - $i + 1) - 1;
                break;
            }
        }

        // 範囲に適合するかどうかを返す
        return $v>=$v_min && $v<=$v_max;
    }

// -- リソースタイプ別処理

    /**
     * data_type="js_code"に固定してbufferを呼び出す
     */
    public function bufferJsCode ($data, $buffer_name="default")
    {
        return $this->buffer($data, "js_code", $buffer_name);
    }

    /**
     * data_type="css_code"に固定してbufferを呼び出す
     */
    public function bufferCssCode ($data, $buffer_name="default")
    {
        return $this->buffer($data, "css_code", $buffer_name);
    }

    /**
     * requiredするためのResourceをbufferに登録
     */
    public function required ($module_name, $required_version="*", $buffer_name="default")
    {
        return $this->buffer($data, "none", $buffer_name)
            ->required($module_name, $required_version);
    }

    /**
     * data_type="js_url"に固定してregisterを呼び出す
     */
    public function registerJsUrl ($module_name, $version, $data)
    {
        return $this->register($module_name, $version, $data, "js_url");
    }

    /**
     * data_type="css_url"に固定してregisterを呼び出す
     */
    public function registerCssUrl ($module_name, $version, $data)
    {
        return $this->register($module_name, $version, $data, "css_url");
    }
}
