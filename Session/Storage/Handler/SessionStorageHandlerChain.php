<?php

namespace Hautelook\SessionStorageChainBundle\Session\Storage\Handler;

use SessionHandlerInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Hautelook\ApiBundle\Exception\InvalidArgumentException;

/**
 * @author Baldur Rensch <brensch@gmail.com>
 */
class SessionStorageHandlerChain implements \SessionHandlerInterface
{
    private $readStorageChain;
    private $writeStorageChain;

    private $container;

    /**
     * Service Constructor
     *
     * @param  ContainerInterface       $container
     * @throws InvalidArgumentException if a service is not defined or is not a SessionHandlerInterface.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container           = $container;
        $readStorageChain          = array();
        $writeStorageChain         = array();

        $parameter = $this->container->getParameter('hautelook_session_storage_chain');

        foreach ($parameter['reader'] as $serviceName) {
            if (!$this->container->has($serviceName)) {
                throw new InvalidArgumentException("Service '{$serviceName}' is not defined");
            }

            $service = $this->container->get($serviceName);

            if (!($service instanceof SessionHandlerInterface)) {
                throw new InvalidArgumentException("Service '{$serviceName}' does not implement 'SessionHandlerInterface'");
            }

            $this->readStorageChain []= $service;
        }

        foreach ($parameter['writer'] as $serviceName) {
            if (!$this->container->has($serviceName)) {
                throw new InvalidArgumentException("Service '{$serviceName}' is not defined");
            }

            $service = $this->container->get($serviceName);

            if (!($service instanceof SessionHandlerInterface)) {
                throw new InvalidArgumentException("Service '{$serviceName}' does not implement 'SessionHandlerInterface'");
            }

            $this->writeStorageChain []= $service;
        }
    }

    /**
     * Open session.
     *
     * @see http://php.net/sessionhandlerinterface.open
     *
     * @param string $savePath    Save path.
     * @param string $sessionName Session Name.
     *
     * @throws \RuntimeException If something goes wrong starting the session.
     *
     * @return boolean
     */
    public function open($savePath, $sessionName)
    {
        foreach ($this->readStorageChain as $storage) {
            $result = $storage->open($savePath, $sessionName);
            if ($result) {
                return true;
            }
        }

        return false;
    }

    /**
     * Close session.
     *
     * @see http://php.net/sessionhandlerinterface.close
     *
     * @return boolean
     */
    public function close()
    {
        $result = true;

        foreach ($this->writeStorageChain as $storage) {
            $result |= $storage->close();
        }

        return $result;
    }

    /**
     * Read session.
     *
     * @see http://php.net/sessionhandlerinterface.read
     *
     * @throws \RuntimeException On fatal error but not "record not found".
     *
     * @return string String as stored in persistent storage or empty string in all other cases.
     */
    public function read($sessionId)
    {
        foreach ($this->readStorageChain as $storage) {
            $result = $storage->read($sessionId);
            if (!empty($result)) {
                return $result;
            }
        }

        return "";
    }

    /**
     * Commit session to storage.
     *
     * @see http://php.net/sessionhandlerinterface.write
     *
     * @param string $sessionId Session ID.
     * @param string $data      Session serialized data to save.
     *
     * @return boolean
     */
    public function write($sessionId, $data)
    {
        $result = true;

        foreach ($this->writeStorageChain as $storage) {
            $result |= $storage->write($sessionId, $data);
        }

        return $result;
    }

    /**
     * Destroys this session.
     *
     * @see http://php.net/sessionhandlerinterface.destroy
     *
     * @param string $sessionId Session ID.
     *
     * @throws \RuntimeException On fatal error.
     *
     * @return boolean
     */
    public function destroy($sessionId)
    {
        $result = true;
        foreach ($this->writeStorageChain as $storage) {
            $result |= $storage->destroy($sessionId);
        }

        return $result;
    }

    /**
     * Garbage collection for storage.
     *
     * @see http://php.net/sessionhandlerinterface.gc
     *
     * @param integer $lifetime Max lifetime in seconds to keep sessions stored.
     *
     * @throws \RuntimeException On fatal error.
     *
     * @return boolean
     */
    public function gc($lifetime)
    {
        $result = true;
        foreach ($this->writeStorageChain as $storage) {
            $result |= $storage->gc($lifetime);
        }

        return $result;
    }
}
