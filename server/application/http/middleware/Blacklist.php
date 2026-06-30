<?php


namespace app\http\middleware;


class Blacklist
{
    public function handle($request, \Closure $next)
    {
        $blacklist = []; // 假设的黑名单IP列表
        if (in_array($request->ip(), $blacklist)) {
            // 如果请求来自黑名单中的IP，返回403禁止访问
            return json(['code' => 403, 'msg' => 'Forbidden'], 403);
        }

        // 如果不在黑名单中，继续请求处理
        return $next($request);
    }
}