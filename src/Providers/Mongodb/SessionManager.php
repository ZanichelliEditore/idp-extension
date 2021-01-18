<?php

namespace Zanichelli\IdpExtension\Providers\Mongodb;

class SessionManager extends \Illuminate\Support\Manager
{
    /**
     * Create an instance of the database session driver.
     *
     * @return \Illuminate\Session\Store
     */
    protected function createMongoDBDriver()
    {
        $connection = $this->getMongoDBConnection();

        $collection = $this->container['config']['session.table'];

        $database = (string) $connection->getMongoDB();

        $handler = new SessionMongoDBWithTokenHandler($connection->getMongoClient(), $this->getMongoDBOptions($database, $collection), $this->container);

        $handler->open(null, 'mongodb');

        return $handler;
    }

    /**
     * Get the database connection for the MongoDB driver.
     *
     * @return Connection
     */
    protected function getMongoDBConnection()
    {
        $connection = $this->container['config']['session.connection'];

        // The default connection may still be mysql, we need to verify if this connection
        // is using the mongodb driver.
        if (is_null($connection)) {
            $default = $this->container['db']->getDefaultConnection();

            $connections = $this->container['config']['database.connections'];

            // If the default database driver is not mongodb, we will loop the available
            // connections and select the first one using the mongodb driver.
            if ($connections[$default]['driver'] != 'mongodb') {
                foreach ($connections as $name => $candidate) {
                    // Check the driver
                    if ($candidate['driver'] == 'mongodb') {
                        $connection = $name;
                        break;
                    }
                }
            }
        }

        return $this->container['db']->connection($connection);
    }

    /**
     * Get the database session options.
     *
     * @return array
     */
    protected function getMongoDBOptions($database, $collection)
    {
        return [
            'database' => $database,
            'collection' => $collection,
            'id_field' => '_id',
            'data_field' => 'payload',
            'time_field' => 'last_activity',
        ];
    }

    /**
     * Get the default session driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return 'mongodb';
    }
}
