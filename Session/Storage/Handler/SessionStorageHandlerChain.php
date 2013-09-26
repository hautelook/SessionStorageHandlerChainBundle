<?php

namespace Hautelook\SessionStorageChainBundle\Session\Storage\Handler;

use SessionHandlerInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use InvalidArgumentException;

/**
 * @author Baldur Rensch <brensch@gmail.com>
 */
class SessionStorageHandlerChain implements \SessionHandlerInterface
{
    private $readStorageChain;
    private $writeStorageChain;

    /**
     * @param array $readers
     * @param array $writers
     * @throws InvalidArgumentException if a service is not defined or is not a SessionHandlerInterface.
     */
    public function __construct(
        array $readers,
        array $writers
    ) {
        $this->readStorageChain = $readers;
        $this->writeStorageChain = $writers;

        foreach (array_merge($this->readStorageChain, $this->writeStorageChain) as $storage) {
            if (!$storage instanceof SessionHandlerInterface) {
                throw new InvalidArgumentException(
                    sprintf(
                        '"%s" should implement "\SessionHandlerInterface"',
                        is_object($storage) ? get_class($storage) : gettype($storage)
                    )
                );
            }
        }
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        $result = true;

        foreach ($this->writeStorageChain as $storage) {
            $result |= $storage->write($sessionId, $data);
        }

        return (boolean) $result;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        $result = true;
        foreach ($this->writeStorageChain as $storage) {
            $result |= $storage->destroy($sessionId);
        }

        return (boolean) $result;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($lifetime)
    {
        $result = true;
        foreach ($this->writeStorageChain as $storage) {
            $result |= $storage->gc($lifetime);
        }

        return (boolean) $result;
    }
}
