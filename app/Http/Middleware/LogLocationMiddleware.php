<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Nette\Schema\Context;

class LogLocationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        
        // log user location before procceding to app
        $log = new Logger('local');
        $handler = new StreamHandler(storage_path() . '/logs/custom-log.log');
        $handler->setFormatter(new LineFormatter(null, null, false, true));
        $log->pushHandler($handler);

        $message = 'Your ip address is: '. $request->ip();

        $context = [];
        $context['phone'] = $request->input('phone', '');
        $log->info($message, $context);

        return $next($request);
    }
}
