<?php
/**
 * Application: DbM Framework
 * A lightweight PHP framework for building web applications.
 *
 * @author Artur Malinowski
 * @copyright Design by Malina (All Rights Reserved)
 * @license MIT
 * @link https://www.dbm.org.pl
 */

declare(strict_types=1);

namespace App\Controller;

use App\Service\IndexService;
use Dbm\Classes\BaseController;
use Dbm\Classes\Http\Request;
use Dbm\Classes\Services\InstallerService;
use Dbm\Interfaces\DatabaseInterface;
use Psr\Http\Message\ResponseInterface;

class IndexController extends BaseController
{
    private readonly InstallerService $installer;

    public function __construct(
        IndexService $indexService,
        ?DatabaseInterface $database = null
    ) {
        parent::__construct($database);

        $this->installer = new InstallerService();
    }

    /**
     * @param IndexService $indexService
     * @return ResponseInterface
     *
     * @Route: "/index"
     */
    public function index(IndexService $indexService): ResponseInterface
    {
        // Create a New Project (templates/index/index.phtml)!
        $this->setFlash('messageInfo', 'Your application is now ready and you can start working on a new project.');

        return $this->render('index/start.phtml', [
            'meta' => $indexService->getMetaIndex(),
        ]);
    }

    /**
     * @param IndexService $indexService
     * @return ResponseInterface
     *
     * @Route: "/start"
     */
    public function start(IndexService $indexService): ResponseInterface
    {
        return $this->render('index/start.phtml', [
            'meta' => $indexService->getMetaStart(),
        ]);
    }

    /**
     * @param IndexService $indexService
     * @param Request $request
     * @return ResponseInterface
     *
     * @Route: "/installer"
     */
    public function installer(IndexService $indexService, Request $request): ResponseInterface
    {
        $dirModule = BASE_DIRECTORY . '_Documents' . DS . 'install';
        $pathManifest = BASE_DIRECTORY . '_Documents' . DS . 'install' . DS . 'module.json';

        if (class_exists('\\App\\Controller\\InstallController')) {
            $action = $request->getQuery('action');

            if ($action === 'remove') {
                $msg = $this->installer->uninstallModule($dirModule, $pathManifest);

                if (!empty($msg)) {
                    $alert = $indexService->alertMessage($msg);
                    $this->setFlash($alert['type'], $alert['message']);
                }

                return $this->redirect('./start');
            } else {
                $this->setFlash('messageInfo', 'The installer has been prepared. <a href="./install" class="fw-bold">Click here to continue &rsaquo;&rsaquo;</a> or if you no longer need it <a href="?action=remove">remove the installer</a>.');
            }
        } else {
            $pathZip = BASE_DIRECTORY . '_Documents' . DS . 'install.zip';

            $msg = $this->installer->installModule($dirModule, $pathManifest, $pathZip);

            if (!empty($msg)) {
                if ($msg['type'] === 'success') {
                    $msg['message'] .= ' <a href="./install">Click here to continue &rsaquo;&rsaquo;</a>';
                }

                $alert = $indexService->alertMessage($msg);
                $this->setFlash($alert['type'], $alert['message']);
            }

            return $this->redirect('./start');
        }

        return $this->render('index/start.phtml', [
            'meta' => $indexService->getMetaInstaller(),
        ]);
    }
}
