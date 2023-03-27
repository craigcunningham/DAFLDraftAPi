<?php
namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    /**
     * Handle an incoming request
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
            //$allowedOrigins = ['example.com', 'example1.com', 'example2.com'];
            //$origin = $_SERVER['HTTP_ORIGIN'];
        
            //if (in_array($origin, $allowedOrigins)) {
            //    return $next($request)
            //        ->header('Access-Control-Allow-Origin', $origin)
            //        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE')
            //        ->header('Access-Control-Allow-Headers', 'Content-Type');

        $headers = [
            'Access-Control-Allow-Origin' => '*',
            //'Access-Control-Allow-Origin' => ['http://localhost:4200', 'https://dafldraftapp.azurewebsites.net/'],
            'Access-Control-Allow-Origin' => 'https://dafldraftapp.azurewebsites.net/',
            'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age' => '86400',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With'
        ];

        if ($request->isMethod('OPTIONS'))
        {
            return response()->json('{"method":"OPTIONS"}', 200, $headers);
        }

        $response = $next($request);
        foreach($headers as $key => $value)
        {
            $response->header($key, $value);
        }

        return $response;
    }
}