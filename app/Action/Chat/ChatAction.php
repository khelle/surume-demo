<?php

namespace App\Action\Chat;

use Surume\Runtime\RuntimeInterface;
use Surume\Transfer\IoConnectionInterface;
use Surume\Transfer\IoMessageInterface;
use Surume\Transfer\IoServerComponentInterface;
use Error;
use SplObjectStorage;

class ChatAction implements IoServerComponentInterface
{
    /**
     * @var RuntimeInterface
     */
    private $runtime;

    /**
     * @var
     */
    private $conns;

    /**
     * @param RuntimeInterface $runtime
     */
    public function __construct(RuntimeInterface $runtime)
    {
        $this->runtime = $runtime;
        $this->conns = new SplObjectStorage();
    }

    /**
     *
     */
    public function __destruct()
    {
        unset($this->runtime);
        unset($this->conns);
    }

    /**
     * @override
     * @inheritDoc
     */
    public function handleConnect(IoConnectionInterface $conn)
    {
        $bubble = [];
        $bubble['name'] = 'User #' . $conn->getResourceId();
        $bubble['date'] = date('Y-m-d H:i:s');
        $bubble['mssg'] = '[Server]: Is online now.';
        $this->broadcast($bubble);

        $this->conns->attach($conn);
    }

    /**
     * @override
     * @inheritDoc
     */
    public function handleDisconnect(IoConnectionInterface $conn)
    {
        $this->conns->detach($conn);

        $bubble = [];
        $bubble['name'] = 'User #' . $conn->getResourceId();
        $bubble['date'] = date('Y-m-d H:i:s');
        $bubble['mssg'] = '[Server]: Is offline now.';
        $this->broadcast($bubble);
    }

    /**
     * @override
     * @inheritDoc
     */
    public function handleMessage(IoConnectionInterface $conn, IoMessageInterface $message)
    {
        $bubble = [];
        $bubble['name'] = 'User #' . $conn->getResourceId();
        $bubble['date'] = date('Y-m-d H:i:s');
        $bubble['mssg'] = $message->read();
        $this->broadcast($bubble);
    }

    /**
     * @override
     * @inheritDoc
     */
    public function handleError(IoConnectionInterface $conn, $ex)
    {
        var_dump('Unhandled Error');
    }

    /**
     * @param mixed[] $message
     */
    protected function broadcast($message)
    {
        foreach ($this->conns as $conn)
        {
            $conn->send((string) json_encode($message));
        }
    }

    /**
     * @return string
     */
    private function getDirPublic()
    {
        return realpath(__DIR__ . '/../../../public');
    }
}