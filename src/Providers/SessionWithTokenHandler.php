<?php

namespace Zanichelli\IdpExtension\Providers;

use Illuminate\Session\DatabaseSessionHandler;

class SessionWithTokenHandler extends DatabaseSessionHandler
{
    /**
     * Add the request information to the session payload.
     *
     * @param  array  $payload
     * @return $this
     */
    protected function addRequestInformation(&$payload)
    {
        if ($this->container->bound('request')) {
            $payload = array_merge($payload, [
                'ip_address' => $this->ipAddress(),
                'user_agent' => $this->userAgent(),
                'token' => $this->token()
            ]);
        }

        return $this;
    }

    /**
     * Get the Token address for the current request.
     *
     * @return string
     */
    protected function token()
    {
        $user = $this->container->make('request')->user();
        if ($user){
            return $user->token;
        }
        return $this->container->make('request')->get('token');
    }
}
