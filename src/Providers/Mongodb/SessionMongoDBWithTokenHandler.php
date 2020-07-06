<?php

namespace Zanichelli\IdpExtension\Providers\Mongodb;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\MongoDbSessionHandler;
use Illuminate\Contracts\Auth\Guard;

class SessionMongoDBWithTokenHandler extends MongoDbSessionHandler
{
    protected $container;

    protected $mongo;

    protected $options;

    protected $collection;

    public function __construct(\MongoDB\Client $mongo, array $options, \Illuminate\Contracts\Foundation\Application $container)
    {
        parent::__construct($mongo, $options);
        $this->container = $container;
        $this->mongo = $mongo;
        $this->options = array_merge([
            'id_field' => '_id',
            'data_field' => 'data',
            'time_field' => 'time',
            'expiry_field' => 'expires_at',
            'token_field' => 'token',
            'user_field' => 'user_id'
        ], $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function doWrite($sessionId, $data)
    {
        $expiry = new \MongoDB\BSON\UTCDateTime((time() + (int) ini_get('session.gc_maxlifetime')) * 1000);

        $fields = [
            $this->options['time_field'] => new \MongoDB\BSON\UTCDateTime(),
            $this->options['expiry_field'] => $expiry,
            $this->options['data_field'] => new \MongoDB\BSON\Binary($data, \MongoDB\BSON\Binary::TYPE_OLD_BINARY),
            $this->options['token_field'] => $this->token(),
            $this->options['user_field'] => $this->userId()
        ];

        $this->getCollection()->updateOne(
            [$this->options['id_field'] => $sessionId],
            ['$set' => $fields],
            ['upsert' => true]
        );

        return true;
    }

    /**
     * Get the Token address for the current request.
     *
     * @return string
     */
    protected function token()
    {
        $user = $this->container->make('request')->user();
        if ($user) {
            return $user->token;
        }
        return $this->container->make('request')->get('token');
    }

    private function getCollection(): \MongoDB\Collection
    {
        if (null === $this->collection) {
            $this->collection = $this->mongo->selectCollection($this->options['database'], $this->options['collection']);
        }

        return $this->collection;
    }
    
    /**
     * Get the currently authenticated user's ID.
     *
     * @return mixed
     */
    protected function userId()
    {
        return $this->container->make(Guard::class)->id();
    }
}
