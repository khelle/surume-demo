<?php

namespace App\Action\Index;

use Surume\Runtime\RuntimeInterface;
use Surume\Transfer\Http\HttpResponse;
use Surume\Transfer\IoConnectionInterface;
use Surume\Transfer\IoMessageInterface;
use Surume\Transfer\IoServerComponentInterface;
use Error;

class IndexAction implements IoServerComponentInterface
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
        $runtime = $this->runtime;
        $runtime
            ->assignWork('index')
            ->then(
                function($value) {
                    return [ 200, $value ];
                },
                function() {
                    return [ 500, null ];
                },
                function() {
                    return [ 408, null ];
                }
            )
            ->spread(
                function($code, $body) use($conn) {
                    $response = new HttpResponse(200, [], $body);
                    $conn->send((string)$response);
                    $conn->close();
                }
            )
        ;
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