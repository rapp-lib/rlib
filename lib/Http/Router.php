<?php
namespace R\Lib\Http;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;

class Router extends FastRoute\Dispatcher
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        // https://github.com/nikic/FastRoute
        $dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '/users', 'get_all_users_handler');
            // {id} must be a number (\d+)
            $r->addRoute('GET', '/user/{id:\d+}', 'get_user_handler');
            // The /{title} suffix is optional
            $r->addRoute('GET', '/articles/{id:\d+}[/{title}]', 'get_article_handler');
        });
        $route = $this->router->dispatch($request->getMethod(), $request->getUri()->getPath());
        if ($route[0] === Dispatcher::NOT_FOUND) {
            return Factory::createResponse(404);
        }
        if ($route[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            return Factory::createResponse(405)->withHeader('Allow', implode(', ', $route[1]));
        }
        foreach ($route[2] as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }
        return $delegate->process($request);
    }
}
