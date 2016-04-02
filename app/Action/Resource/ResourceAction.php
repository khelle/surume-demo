<?php

namespace App\Action\Resource;

use Surume\Runtime\RuntimeInterface;
use Surume\Transfer\Http\HttpResponse;
use Surume\Transfer\IoConnectionInterface;
use Surume\Transfer\IoMessageInterface;
use Surume\Transfer\IoServerComponentInterface;
use Error;

class ResourceAction implements IoServerComponentInterface
{
    /**
     * @var RuntimeInterface
     */
    protected $runtime;

    /**
     * @param RuntimeInterface $runtime
     */
    public function __construct(RuntimeInterface $runtime)
    {
        $this->runtime = $runtime;
    }

    /**
     *
     */
    public function __destruct()
    {
        unset($this->runtime);
    }

    /**
     * @override
     * @inheritDoc
     */
    public function handleConnect(IoConnectionInterface $conn)
    {}

    /**
     * @override
     * @inheritDoc
     */
    public function handleDisconnect(IoConnectionInterface $conn)
    {}

    /**
     * @override
     * @inheritDoc
     */
    public function handleMessage(IoConnectionInterface $conn, IoMessageInterface $message)
    {
        $filePath = realpath($this->getDirPublic());
        $filePath .= $message->getRequestTarget();

        if (!file_exists($filePath))
        {
            $conn->send(new HttpResponse(404));
            $conn->close();
            return;
        }

        $response = new HttpResponse(200, [], file_get_contents($filePath));

        $conn->send((string)$response);
        $conn->close();
    }

    /**
     * @override
     * @inheritDoc
     */
    public function handleError(IoConnectionInterface $conn, $ex)
    {}

    /**
     * @return string
     */
    private function getDirPublic()
    {
        return realpath(__DIR__ . '/../../../public');
    }
}