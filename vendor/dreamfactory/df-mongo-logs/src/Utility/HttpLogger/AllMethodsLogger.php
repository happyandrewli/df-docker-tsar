<?php

namespace DreamFactory\Core\MongoLogs\Utility\HttpLogger;

use Illuminate\Http\Request;
use Spatie\HttpLogger\LogProfile;

class AllMethodsLogger implements LogProfile
{
    public function shouldLogRequest(Request $request): bool
    {
        return in_array(strtolower($request->method()), ['get', 'post', 'put', 'patch', 'delete']);
    }
}
