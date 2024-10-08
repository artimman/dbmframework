<?php
/*
 * DbM Framework
 * All code copyright Design by Malina
 * DbM: www.dbm.org.pl
 */

declare(strict_types=1);

namespace App\Controller;

use App\Model\PageModel;
use App\Service\PageService;

;
use Dbm\Classes\BaseController;
use Dbm\Interfaces\DatabaseInterface;

class PageController extends BaseController
{
    private $model;
    private $service;

    public function __construct(?DatabaseInterface $database = null)
    {
        parent::__construct($database);

        $this->model = new PageModel();
        $this->service = new PageService($this->model);
    }

    /* @Route: "/page" */
    public function index()
    {
        $this->render('page/index.phtml', [
            'meta' => $this->service->getMetaPage(),
            'content' => $this->model->Content(),
        ]);
    }

    /* @Route: "/page/site" or for example "/website-title.site.html" */
    public function siteMethod()
    {
        $this->render('page/site.phtml', [
            'meta' => $this->service->getMetaPage(),
            'content' => $this->model->Content(),
        ]);
    }

    /* @Route: website-title.offer.html */
    public function offerMethod()
    {
        $this->render('page/offer.phtml', [
            'meta' => $this->service->getMetaPage(),
            'content' => $this->model->Content(),
        ]);
    }
}
