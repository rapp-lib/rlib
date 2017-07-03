<?php
namespace R\Lib\Http;
use Zend\Diactoros\ServerRequestFactory as ZendServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequestFactory
{
    /**
     * スーパーグローバル変数をもとに構築
     */
    public static function fromGlobals ($webroot, $request=array())
    {
        $server = $request["server"];
        if ( ! $server) {
            // $_SERVER["DOCUMENT_ROOT"]の正規化
            $server = $_SERVER;
            $script_name = $server['SCRIPT_NAME'];
            $script_file_name = $server['SCRIPT_FILENAME'];
            if (substr($script_file_name, -strlen($script_name)) === $script_name) {
                $server["DOCUMENT_ROOT"] = substr($script_file_name, 0, -strlen($script_name));
            }
        }
        $server = ZendServerRequestFactory::normalizeServer($server);
        $headers = ZendServerRequestFactory::marshalHeaders($server);
        return self::build($webroot, array(
            "server"  => $server,
            "files"   => ZendServerRequestFactory::normalizeFiles($request["files"] ?: $_FILES),
            "headers" => $headers,
            "uri"     => ZendServerRequestFactory::marshalUriFromServer($server, $headers),
            "method"  => ZendServerRequestFactory::get('REQUEST_METHOD', $server, 'GET'),
            "body"    => $request["body"] ?: 'php://input',
            "cookie_params" => $request["cookies"] ?: $_COOKIE,
            "query_params"  => $request["get"] ?: $_GET,
            "parsed_body"   => $request["post"] ?: $_POST,
        ));
    }
    /**
     * ServerRequestInterfaceをもとに構築
     */
    public static function fromServerRequestInterface ($webroot, ServerRequestInterface $request)
    {
        return self::build($webroot, array(
            "server"  => $request->getServerparams(),
            "files"   => $request->getUploadedFiles(),
            "headers" => $request->getHeaders(),
            "uri"     => $request->getUri(),
            "method"  => $request->getMethod(),
            "body"    => $request->getBody(),
            "cookie_params" => $request->getCookieParams(),
            "query_params"  => $request->getQueryParams(),
            "parsed_body"   => $request->getParsedBody(),
        ));
    }
    /**
     * 規定の構成情報をもとに構築
     */
    public static function build ($webroot, array $attrs)
    {
        // Webrootへの反映
        $webroot->updateByRequest($attrs["server"]["DOCUMENT_ROOT"], $attrs["uri"]);
        // Uriの構築
        $attrs["uri"] = $webroot->uri($attrs["uri"]);
        // ServerRequestの構築
        $server_request = new ServerRequest($attrs["server"], $attrs["files"],
            $attrs["uri"], $attrs["method"], $attrs["body"], $attrs["headers"]);
        // ServerRequestの更新
        $server_request = $server_request
            ->withCookieParams($attrs["cookie_params"])
            ->withQueryParams($attrs["query_params"])
            ->withParsedBody($attrs["parsed_body"]);
        $server_request = $server_request
            ->withAttribute(InputValues::ATTRIBUTE_INDEX, new InputValues($server_request));
        return $server_request;
    }
}
