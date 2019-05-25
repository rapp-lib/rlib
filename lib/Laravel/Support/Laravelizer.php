<?php
namespace R\Lib\Laravel\Support;
/*
PathとUrlの扱い
    サブディレクトリ設定は、Rewrite設定側でBaseUriを除去して実現
    出力時には.envのAPP_URLの値で補完してURL生成する
    request()->path()でRequestPathが取れる("/"は削除済み)
    request()->url()でRequestUriが取れる
    request()->route()->uri()でRouteに指定したパターンが取れる
*/
class Laravelizer
{
    public function convertPathToRoute($path)
    {
        $path = trim($path, "/");
        try {
            $request = \Request::create($path);
            return \Route::getRoutes()->match($request);
        } catch(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return null;
        }
    }
    public function convertPageToRoute($page)
    {
        if (starts_with($page, ".")) {
            $parts = explode('.', app()->request->route()->getName(), 2);
            if ($page===".") {
                $page = $parts[0].".".$parts[1];
            } else {
                $page = $parts[0].$page;
            }
        }
        return app('router')->getRoutes()->getByName($page);
    }
    public function convertPathToUrl($path, $params=array(), $anchor=null)
    {
        $route = $this->convertPathToRoute($path);
        // if ($route && in_array('csrf_check', $route->middleware())) {
        //     $key = app()->security->getCsrfTokenName();
        //     $value = app()->security->getCsrfToken();
        //     $params[$key] = $value;
        // }
        // debug($path." => ".url($path, $params));
        return url($path, $params);
    }
    public function convertPageToUrl($page, $params=array(), $anchor=null)
    {
        $route = $this->convertPageToRoute($page);
        if ( ! $route) {
            report_warning("PageID not found ".$page);
            return null;
        }
        return $this->convertPathToUrl($route->uri());
    }
    public function convertPathToFile($path)
    {
        return constant("R_APP_ROOT_DIR")."/public/".$path;
    }
    public function getWebrootBaseUri()
    {
        return config('app.url');
    }
    public function getWebrootBaseDir()
    {
        return constant("R_APP_ROOT_DIR")."/public";
    }
    public function convertPageToAction($page)
    {
        $parts = explode(".", $page, 2);
        return '\R\App\Controller\\'.str_camelize($parts[0])
            .'Controller@act_'.$parts[1];
    }
    public function getRouteRole($route)
    {
        $shorthands = $route->getMiddleware();
        foreach ($shorthands as $shorthand) {
            $attrs = explode(":", $shorthand);
            if ($attrs[0] === "auth") return $attrs[1];
            elseif ($attrs[0] === "noauth") return $attrs[1];
        }
        return null;
    }
    public function loadRoutingConfig()
    {
        $file = constant("R_APP_ROOT_DIR")."/config/routing.config.php";
        $config = file_exists($file) ? include($file) : array();
        foreach ((array)$config["http.webroots.www.routes"] as $set) {
            foreach ((array)$set[0] as $record) {
                $name = $record[0];
                $route_uri = $record[1];
                $action = $this->convertPageToAction($name);
                $route = \Route::any($route_uri, $action)->name($name);
                $route->middleware('web');
                // auth
                $role = $set[2]["auth.role"];
                $priv_req_1 = $set[2]["auth.priv_req"];
                $priv_req_2 = $record[2]["auth.priv_req"];
                $priv_req = isset($priv_req_2) ? $priv_req_2 : $priv_req_1;
                if ($priv_req) $route->middleware('auth:'.$role);
                elseif ($priv_req) $route->middleware('noauth:'.$role);
                $static_route = $record[2]["static_route"];
                $csrf_check = $record[2]["csrf_check"];
            }
        }
    }
    public function loadAuthConfig()
    {
        $file = constant("R_APP_ROOT_DIR")."/config/auth.config.php";
        $config = file_exists($file) ? include($file) : array();
        \Auth::provider("role", function($app, $config){
            return new \R\Lib\Laravel\Auth\UserProvider\RoleUserProvider(
                $config["role_name"],
                $config["role_config"]
            );
        });
        foreach ((array)$config["auth.roles"] as $role_name=>$role_config) {
            app()->config["auth.providers.role_".$role_name] = array(
                'driver' => 'role',
                'role_name' => $role_name,
                'role_config' => $role_config["login.options"],
            );
            app()->config["auth.guards.".$role_name] = array(
                'driver' => 'session',
                'provider' => "role_".$role_name,
            );
        }
    }
    public function getAssetBaseDir()
    {
        return constant("R_APP_ROOT_DIR")."/public/.assets";
    }
    public function getAssetBaseUri()
    {
        return url(".assets");
    }
    public function convertUriToFile($uri)
    {
        if (Str::startsWith($uri, '/')) {
            $uri = substr($uri, 1);
        }
        if (! Str::startsWith($uri, 'http')) {
            $uri = $this->getWebrootBaseUri().'/'.$uri;
        }
        return $this->getWebrootBaseDir()."/".trim($uri, '/');
    }
    public function completeDirectoryIndex($file)
    {
        if (preg_match('!/$!', $file)) {
            $file .= 'index.html';
        } elseif ( ! preg_match('!\.\w+$!', $file)) {
            $file .= '/index.html';
        }
        return $file;
    }
    public function getPageIsAccessible($page)
    {
        return true;
    }
    public function getPriv($role, $priv_id)
    {
        return null;
    }
}