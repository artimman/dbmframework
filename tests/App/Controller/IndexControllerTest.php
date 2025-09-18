<?php

declare(strict_types=1);

namespace Tests\App\Controller;

use App\Controller\IndexController;
use Dbm\Interfaces\DatabaseInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class IndexControllerTest extends TestCase
{
    private IndexController&MockObject $controller;
    private DatabaseInterface&MockObject $databaseMock;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        /** @var DatabaseInterface&MockObject $databaseMock */
        $this->databaseMock = $this->createMock(DatabaseInterface::class);

        $this->controller = $this->getMockBuilder(IndexController::class)
            ->setConstructorArgs([$this->databaseMock])
            ->onlyMethods(['isConfigValid', 'redirect', 'render', 'setFlash'])
            ->getMock();
    }

    public function testStartRendersCorrectTemplate()
    {
        $this->controller->expects($this->once())
            ->method('render')
            ->with('index/start.phtml');

        $this->controller->start();
    }
}
