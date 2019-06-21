<?php
namespace R\Lib\Laravel\Http\Middleware;

class TagRegistry
{
  public function handle($request, $next, $tags="")
  {
    $tags = explode(":", $tags);
    if ( ! $request->tags) $request->tags = array();
    foreach ($tags as $tag) $request->tags[$tag] = $tag;
    return $next($request);
  }
}
