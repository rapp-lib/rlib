<?php
/**
 * @require-deprecated tag, path_to_url
 */
namespace R\Lib\Frontend;

/**
 *
 */
class FrontendResourceManager
{
    private static $instance = null;

    private $modules = array();
    private $buffer = array();
    private $asset_urls = array();

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
     * @setter
     */
    public function setAssetUrl ($asset_group_name, $url)
    {
        $this->asset_urls[$asset_group_name] = $url;
    }

    /**
     * @getter
     */
    public function getAssetUrl ($asset_group_name)
    {
        if (isset($this->asset_urls[$asset_group_name])) {
            return $this->asset_urls[$asset_group_name];
        }
        return path_to_url("/asset/".$asset_group_name);
    }

// -- モジュール依存管理

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
     * Bufferに登録されたデータを出力する
     * 出力時に依存関係を解決を行う
     */
    public function flush ($buffer_name="default")
    {
        $html = "";
        foreach ((array)$this->buffer[$buffer_name] as $resource) {
            $html .= $resource->getHtmlWithDepenedencies();
        }
        return $html;
    }

    /**
     * Moduleを読み込み登録済みとして記録
     */
    public function markLoaded ($module_name, $version)
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
        ));
    }

    /**
     * 要求バージョン指定に適合するかチェック
     */
    private function checkVersion ($version, $required_version)
    {
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
     * data_type="js_url"に固定してregisterを呼び出す
     */
    public function registerJsUrl ($module_name, $version, $data)
    {
        return $this->register($module_name, $version, $data, "js_url");
    }

    /**
     * data_type="css_code"に固定してbufferを呼び出す
     */
    public function bufferCssCode ($data, $buffer_name="default")
    {
        return $this->buffer($data, "css_code", $buffer_name);
    }

    /**
     * data_type="css_url"に固定してregisterを呼び出す
     */
    public function registerCssUrl ($module_name, $version, $data)
    {
        return $this->register($module_name, $version, $data, "css_url");
    }
}
