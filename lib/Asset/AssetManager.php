<?php
/**
 * @require functions/tag
 */
namespace R\Lib\Asset;

/**
 * リソース管理機能
 */
class AssetManager
{
    private $modules = array();
    private $buffer = array();
    private $assets_urls = array();
    private static $state_ids = array(
        "html.before" => 0,
        "head.start" => 1,
        "head.end" => 2,
        "body.start" => 3,
        "body.end" => 4,
        "html.end" => 5,
    );
    private $state = 0;

    public function __construct ()
    {
    }

// -- アセット管理

    /**
     * アセットDIR/URLを登録する
     * アセットDIR以下の.assets.phpからモジュールカタログを読み込む
     */
    public function loadAssetCatalog ($catalog_config)
    {
        if (is_array($catalog_config)) {
            $catalog_php = $catalog_config["catalog_php"];
            $url = $catalog_config["url"];
        } elseif (is_a($catalog_config,'R\Lib\Route\Route')) {
            $catalog_php = $catalog_config->getFile();
            $url = dirname($catalog_config->getUrl());
        }
        $catalog_php = realpath($catalog_php);
        // ファイルがなければエラー
        if ( ! file_exists($catalog_php)) {
            report_warning("AssetCatalogの指定が不正です",array(
                "asset_catalog_php" => $asset_catalog_php,
                "url" => $url,
            ));
            return;
        }
        // アセットカタログPHPを読み込む
        if ( ! $this->assets_dirs[$catalog_php]) {
            $asset = $this;
            include($catalog_php);
        }
        $this->assets_dirs[$catalog_php] = $url;
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
     * Buffer済みのAssetを作成
     */
    public function buffer ($data, $data_type, $buffer_name)
    {
        $asset = new Asset($this, $data, $data_type);
        $this->buffer[$buffer_name][] = $asset;
        return $asset;
    }

    /**
     * Bufferに登録されたデータを出力する
     * 出力時に依存関係を解決を行う
     * headを指定するとCSSコード/URLのみを読み込む
     */
    public function flush ($buffer_name="*")
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
            foreach ((array)$this->buffer[$buffer_name_str] as $asset) {
                $html .= $asset->getHtmlWithDepenedencies();
            }
        }
        return $html;
    }

// -- モジュール依存管理

    /**
     * モジュールとして登録されたAssetを作成
     */
    public function register ($module_version, $data, $data_type="none")
    {
        list($module_name, $version) = $this->extractModuleVersion($module_version);
        if ( ! $version) {
            $version = "0";
        }
        $asset = new Asset($this, $data, $data_type);
        $asset->setModuleName($module_name, $version);
        $this->modules[$module_name][$version] = $asset;
        return $asset;
    }

    /**
     * Moduleを読み込み登録済みとして記録
     */
    public function loaded ($module_version)
    {
        list($module_name, $version) = $this->extractModuleVersion($module_version);
        $this->loaded_modules[$module_name] = $version;
    }

    /**
     * 登録済みのModuleを読み込む
     */
    public function getRegisteredModule ($required_module_version)
    {
        list($module_name, $required_version) = $this->extractModuleVersion($required_module_version);
        // 適合する最新版のモジュールを読み込む
        if (is_array($this->modules[$module_name])) {
            krsort($this->modules[$module_name]);
            foreach ((array)$this->modules[$module_name] as $version => $asset) {
                if ($this->checkVersion($version, $required_version)) {
                    return $asset;
                }
            }
        }
        return null;
    }

    /**
     * Moduleを読み込む
     */
    public function load ($required_module_version)
    {
        list($module_name, $required_version) = $this->extractModuleVersion($required_module_version);
        // モジュールが読み込み済みの場合
        $loaded_version = $this->loaded_modules[$module_name];
        if (isset($loaded_version)) {
            // 適合しないバージョンが読み込まれていればエラー
            if ( ! $this->checkVersion($loaded_version, $required_version)) {
                report_error("読み込み済みモジュールが適合しません",array(
                    "module_name" => $module_name,
                    "loaded_version" => $loaded_version,
                    "required_version" => $required_version,
                    "loaded_modules" => $this->loaded_modules,
                ));
            }
            return "";
        }

        $asset = $this->getRegisteredModule($required_module_version);
        // 読み込めない場合はエラー
        if ( ! $asset) {
            report_error("依存モジュールが読み込めませんでした",array(
                "module_name" => $module_name,
                "required_version" => $required_version,
                "modules" => $this->modules,
            ));
        }
        // ロード済みとして記録してHTMLを返す
        $this->loaded_modules[$module_name] = $asset->getVersion();
        return $asset->getHtmlWithDepenedencies();
    }

    /**
     * モジュールバージョン指定を分解する
     */
    private function extractModuleVersion ($module_version)
    {
        return explode(":",$module_version);
    }

    /**
     * 要求バージョン指定に適合するかチェック
     */
    private function checkVersion ($version, $required_version)
    {
        if ( ! is_numeric($required_version) && ! strlen($required_version)) {
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
    public function bufferJsCode ($data, $buffer_name="script")
    {
        return $this->buffer($data, "js_code", $buffer_name);
    }

    /**
     * data_type="css_code"に固定してbufferを呼び出す
     */
    public function bufferCssCode ($data, $buffer_name="css")
    {
        return $this->buffer($data, "css_code", $buffer_name);
    }

    /**
     * requiredするためのAssetをbufferに登録
     */
    public function required ($module_version, $buffer_name="html")
    {
        list($module_name, $version) = $this->extractModuleVersion($module_version);
        return $this->buffer($data, "none", $buffer_name)
            ->required($module_name, $version);
    }

    /**
     * data_type="js_url"に固定してregisterを呼び出す
     */
    public function registerJsUrl ($module_version, $data)
    {
        return $this->register($module_version, $data, "js_url");
    }

    /**
     * data_type="css_url"に固定してregisterを呼び出す
     */
    public function registerCssUrl ($module_version, $data)
    {
        return $this->register($module_version, $data, "css_url");
    }
}
