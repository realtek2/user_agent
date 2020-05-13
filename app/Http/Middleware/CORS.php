<?php 

namespace App\Http\Middleware;
use Closure;

class CORS {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        header("Access-Control-Allow-Origin: ".$request->headers->get('origin'));

        // ALLOW OPTIONS METHOD
        $headers = [
            'Access-Control-Allow-Methods'     => 'POST, OPTIONS',
            'Access-Control-Allow-Headers'     => '*',
            'Access-Control-Allow-Credentials' => 'true',
            "Access-Control-Max-Age"           => "-1" //600
        ];
        if($request->getMethod() == "OPTIONS") {
            // The client-side application can set only headers allowed in Access-Control-Allow-Headers
            return Response::make('OK', 200, $headers);
        }

        $response = $next($request);
        foreach($headers as $key => $value){
            $response->header($key, $value);
        }
        return $response;
    }

}