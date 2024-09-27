<?php
/*
 * DbM Framework (PHP MVC Simple CMS)
 * All code copyright Design by Malina
 * DbM: www.dbm.org.pl
 *
 * INFO! Rozbij PanelController na wiecej kontrolerow panelu i zoptymalizuj kod!
 * Przenies powtarzalny kod konstruktora, sprawdzenie sesji itp. do jednej klasy np. AdminBaseController?
 */

declare(strict_types=1);

namespace App\Controller;

use App\Config\ConstantConfig;
use App\Model\PanelModel;
use App\Utility\MethodsUtility;
use App\Utility\ResizeUploadImageUtility;
use Dbm\Classes\AdminBaseController;
use Dbm\Interfaces\DatabaseInterface;
use DateTime;

class PanelController extends AdminBaseController
{
    private const DIR_CONTENT = BASE_DIRECTORY . 'data/content/';
    private const DIR_LOG = BASE_DIRECTORY . 'var/log/';
    private const DIR_IMG_BLOG = BASE_DIRECTORY . 'public/images/blog/photo/';
    private const DIR_IMG_SECTION = BASE_DIRECTORY . 'public/images/blog/category/photo/';

    private $model;

    public function __construct(?DatabaseInterface $database = null)
    {
        parent::__construct($database);

        $this->model = new PanelModel($database);
    }

    public function index()
    {
        $translation = $this->translation;

        $contentFiles = array_diff(scandir(self::DIR_CONTENT), array('..', '.'));

        $allArticles = $this->model->getAllArticlesLimit(10);

        $arrayArticles = array();

        foreach ($allArticles as $article) {
            $arrayArticles[] = $article->page_header;
        }

        $meta = array(
            'meta.title' => $translation->trans('website.name') . ' - Panel',
        );

        $this->render('panel/admin.phtml', [
            'meta' => $meta,
            'files' => $contentFiles,
            'articles' => $arrayArticles,
        ]);
    }

    public function manageBlogMethod()
    {
        if ($this->requestData('action') == 'delete') {
            $this->setFlash('message' . ucfirst($this->requestData('status')), $this->requestData('message'));
        }

        $allArticles = $this->model->getJoinArticlesFirst();

        $meta = array(
            'meta.title' => 'manageBlogMethod',
        );

        $this->render('panel/manage_blog.phtml', [
            'meta' => $meta,
            'articles' => $allArticles,
        ]);
    }

    public function createOrEditBlogMethod()
    {
        $id = (int) $this->requestData('id');
        $imageFiles = array_diff(scandir(self::DIR_IMG_BLOG), array('..', '.'));
        $allSections = $this->model->arraySections();
        $allUsers = $this->model->arrayUsers();
        $dataArticle = $this->model->getArticle($id);

        $fields = [];

        if ($dataArticle) {
            $fields = (object) [
                'keywords' => $dataArticle->meta_keywords,
                'description' => $dataArticle->meta_description,
                'title' => $dataArticle->meta_title,
                'header' => $dataArticle->page_header,
                'content' => $dataArticle->page_content,
                'image' => $dataArticle->image_thumb,
                'sid' => (int) $dataArticle->section_id,
                'uid' => (int) $dataArticle->user_id,
            ];
        }

        if (!empty($id) && ($id !== 0)) {
            $meta = [
                'meta.title' => "Article editing - Dashboard DbM Framework",
            ];

            $page = [
                'meta.title' => "Article editing - Dashboard DbM Framework",
                'header' => "Editing article",
                'action' => "editBlog",
                'submit' => '<i class="fa fa-edit mr-2"></i>Edit',
                'id' => $id,
            ];
        } else {
            $meta = [
                'meta.title' => "Article create - Dashboard DbM Framework",
            ];

            $page = [
                'header' => "Create article",
                'action' => "createBlog",
                'submit' => '<i class="fas fa-plus mr-2"></i>Create',
                'id' => $id,
                'accordion' => true,
            ];
        }

        $this->render('panel/create_edit_blog.phtml', [
            'meta' => $meta,
            'page' => $page,
            'fields' => !empty($fields) ? $fields : null,
            'images' => $imageFiles,
            'sections' => $allSections,
            'users' => $allUsers,
        ]);
    }

    public function createBlogMethod()
    {
        $keywords = $this->requestData('keywords');
        $description = $this->requestData('description');
        $title = $this->requestData('title');
        $header = $this->requestData('header');
        $content = $this->requestData('content');
        $section = $this->requestData('section');
        $user = $this->requestData('user');
        $image = $this->requestData('image');

        $imageFiles = array_diff(scandir(self::DIR_IMG_BLOG), array('..', '.'));
        $allSections = $this->model->arraySections();
        $allUsers = $this->model->arrayUsers();

        $meta = [
            'meta.title' => "Article create - Dashboard DbM Framework",
        ];

        $page = [
            'header' => "Create article",
            'action' => "createBlog",
            'submit' => '<i class="fas fa-plus mr-2"></i>Create',
            'accordion' => true,
        ];

        $fields = (object) [
            'keywords' => $keywords,
            'description' => $description,
            'title' => $title,
            'header' => $header,
            'content' => $content,
            'image' => $image,
            'sid' => $section,
            'uid' => $user,
        ];

        $errorValidate = $this->model->validateFormBlog($keywords, $description, $title, $header, $content, $section, $user);

        if (empty($errorValidate)) {
            $userId = (int) $this->requestData('user');
            $sectionId = (int) $this->requestData('section');
            $lastId = false;

            empty($image) ? $image = null : false;

            $sqlInsert = [':uid' => $userId, 'sid' => $sectionId, ':title' => $title, ':description' => $description,
                ':keywords' => $keywords, ':header' => $header, ':content' => $content, ':thumb' => $image];

            if ($this->model->insertArticle($sqlInsert)) {
                $lastId = $this->model->getLastId();
                $this->setFlash('messageSuccess', 'The new article has been successfully created.');
            } else {
                $this->setFlash('messageDanger', 'An unexpected error occurred!');
            }

            $this->redirect("./panel/createOrEditBlog", ['id' => $lastId]);
        } else {
            $this->render('panel/create_edit_blog.phtml', [
                'meta' => $meta,
                'page' => $page,
                'fields' => $fields,
                'images' => $imageFiles,
                'sections' => $allSections,
                'users' => $allUsers,
                'validate' => !empty($errorValidate) ? $errorValidate : null,
            ]);
        }
    }

    public function editBlogMethod()
    {
        $id = (int) $this->requestData('id');
        $keywords = $this->requestData('keywords');
        $description = $this->requestData('description');
        $title = $this->requestData('title');
        $header = $this->requestData('header');
        $content = $this->requestData('content');
        $section = $this->requestData('section');
        $user = $this->requestData('user');

        !empty($this->requestData('image')) ? $image = $this->requestData('image') : $image = null;

        $datetime = new DateTime();
        $dateNow = $datetime->format('Y-m-d H:i:s');

        $sqlUpdate = [':uid' => $user, 'sid' => $section, ':title' => $title, ':description' => $description, ':keywords' => $keywords,
            ':header' => $header, ':content' => $content, ':thumb' => $image, ':date' => $dateNow, ':id' => $id];

        if ($this->model->updateArticle($sqlUpdate)) {
            $this->setFlash('messageSuccess', 'The article has been successfully edited.');
        } else {
            $this->setFlash('messageDanger', 'An unexpected error occurred!');
        }

        $this->redirect("./panel/createOrEditBlog", ['id' => $id]);
    }

    public function manageBlogSectionsMethod()
    {
        if ($this->requestData('action') == 'delete') {
            $this->setFlash('message' . ucfirst($this->requestData('status')), $this->requestData('message'));
        }

        $querySections = $this->model->getAllSections();

        $meta = array(
            'meta.title' => 'manageBlogSectionsMethod',
        );

        $this->render('panel/manage_blog_sections.phtml', [
            'meta' => $meta,
            'sections' => $querySections,
        ]);
    }

    public function createOrEditBlogSectionMethod()
    {
        $id = (int) $this->requestData('id');
        $imageFiles = array_diff(scandir(self::DIR_IMG_SECTION), array('..', '.'));
        $dataSection = $this->model->getSection($id);

        $fields = [];

        if ($dataSection) {
            $fields = (object) [
                'keywords' => $dataSection->section_keywords,
                'description' => $dataSection->section_description,
                'name' => $dataSection->section_name,
                'image' => $dataSection->image_thumb,
            ];
        }

        if (!empty($id) && ($id !== 0)) {
            $meta = [
                'meta.title' => "Section editing - Dashboard DbM Framework",
            ];

            $page = [
                'header' => "Editing section",
                'action' => "editSection",
                'submit' => '<i class="fa fa-edit mr-2"></i>Edit',
                'id' => $id,
            ];
        } else {
            $meta = [
                'meta.title' => "Section create - Dashboard DbM Framework",
            ];

            $page = [
                'header' => "Create section",
                'action' => "createSection",
                'submit' => '<i class="fas fa-plus mr-2"></i>Create',
                'id' => $id,
            ];
        }

        $this->render('panel/create_edit_blog_section.phtml', [
            'meta' => $meta,
            'page' => $page,
            'images' => $imageFiles,
            'fields' => !empty($fields) ? $fields : null,
        ]);
    }

    public function createSectionMethod()
    {
        $keywords = $this->requestData('keywords');
        $description = $this->requestData('description');
        $name = $this->requestData('name');
        $image = $this->requestData('image');

        $imageFiles = array_diff(scandir(self::DIR_IMG_SECTION), array('..', '.'));

        $objectFields = (object) [
            'keywords' => $keywords,
            'description' => $description,
            'name' => $name,
            'image' => $image,
        ];

        $errorValidate = $this->model->validateFormBlogSection($name, $description, $keywords);

        if (empty($errorValidate)) {
            $lastId = false;

            empty($image) ? $image = null : false;

            $sqlInsert = [':name' => $name, ':description' => $description, ':keywords' => $keywords, ':thumb' => $image];

            if ($this->model->insertSection($sqlInsert)) {
                $lastId = $this->model->getLastId();
                $this->setFlash('messageSuccess', 'The new section has been successfully created.');
            } else {
                $this->setFlash('messageDanger', 'An unexpected error occurred!');
            }

            $this->redirect("./panel/createOrEditBlogSection", ['id' => $lastId]);
        } else {
            $this->render('panel/create_edit_blog_section.phtml', [
                'meta' => [
                    'meta.title' => "Section create - Dashboard DbM Framework",
                ],
                'page' => [
                    'header' => "Create section",
                    'action' => "createSection",
                    'submit' => '<i class="fas fa-plus mr-2"></i>Create',
                ],
                'images' => $imageFiles,
                'fields' => $objectFields,
                'validate' => !empty($errorValidate) ? $errorValidate : null,
            ]);
        }
    }

    public function editSectionMethod()
    {
        $id = (int) $this->requestData('id');
        $keywords = $this->requestData('keywords');
        $description = $this->requestData('description');
        $name = $this->requestData('name');

        !empty($this->requestData('image')) ? $image = $this->requestData('image') : $image = null;

        $sqlUpdate = [':name' => $name, ':description' => $description, ':keywords' => $keywords, ':thumb' => $image, ':id' => $id];

        if ($this->model->updateSection($sqlUpdate)) {
            $this->setFlash('messageSuccess', 'The section has been successfully edited.');
        } else {
            $this->setFlash('messageDanger', 'An unexpected error occurred!');
        }

        $this->redirect("./panel/createOrEditBlogSection", ['id' => $id]);
    }

    public function toolsLogsMethod()
    {
        $action = $this->requestData('action');
        $file = $this->requestData('file');
        $pathFile = self::DIR_LOG . $file;

        if (!empty($file) && (file_exists($pathFile) && (filesize($pathFile) > 0))) {
            if ($action == 'delete') {
                unlink($pathFile);
            } else {
                $contentPreview = file_get_contents($pathFile);
                $contentPreview = str_replace(["\n"], ["<br>"], $contentPreview);
            }
        }

        if (is_dir(self::DIR_LOG)) {
            $contentFiles = array_diff(scandir(self::DIR_LOG), array('..', '.', 'mailer'));
        }

        $this->render('panel/logs.phtml', [
            'meta' => ['meta.title' => 'errorLogs'],
            'files' => $contentFiles ?? null,
            'preview' => $contentPreview ?? null,
        ]);
    }

    public function ajaxUploadImageMethod(): void
    {
        $type = $this->requestData('type');

        ($type === 'blog') ? $pathImage = ConstantConfig::PATH_BLOG_IMAGES : $pathImage = ConstantConfig::PATH_PAGE_IMAGES;

        if (!empty($_FILES['file'])) {
            $fileName = $_FILES["file"]["name"];
            $fileTempName = $_FILES["file"]["tmp_name"];

            $imageUpload = new ResizeUploadImageUtility();
            $arrayResult = $imageUpload->createImages($fileTempName, $fileName, $pathImage);

            echo json_encode($arrayResult);
        } else {
            echo json_encode(['status' => "danger", 'message' => "Please select an image to upload!"]);
        }
    }

    public function ajaxDeleteImageMethod(): void
    {
        $file = $this->requestData('file');
        $type = $this->requestData('type');

        ($type === 'blog') ? $pathImage = ConstantConfig::PATH_BLOG_IMAGES : $pathImage = ConstantConfig::PATH_PAGE_IMAGES;

        $pathPhoto = $pathImage . 'photo/' . $file;
        $pathThumb = $pathImage . 'thumb/' . $file;

        $methodUtility = new MethodsUtility();
        $deleteImages = $methodUtility->fileMultiDelete([$pathPhoto, $pathThumb]);

        if ($deleteImages !== null) {
            echo json_encode(['status' => "danger", 'message' => $deleteImages]);
        } else {
            echo json_encode(['status' => "success", 'message' => "The image has been successfully deleted."]);
        }
    }

    public function ajaxDeleteArticleMethod(): void
    {
        $articleId = (int) $this->requestData('id');

        if ($this->model->deleteArticle($articleId)) {
            echo json_encode(['status' => "success", 'message' => 'The article has been successfully deleted.']);
        } else {
            echo json_encode(['status' => "danger", 'message' => 'An unexpected error occurred!']);
        }
    }

    public function ajaxDeleteSectionMethod(): void
    {
        $sectionId = (int) $this->requestData('id');

        if ($this->model->deleteSection($sectionId)) {
            echo json_encode(['status' => "success", 'message' => 'The section has been successfully deleted.']);
        } else {
            echo json_encode(['status' => "danger", 'message' => 'An unexpected error occurred!']);
        }
    }

    public function ajaxUploadImageSectionMethod(): void
    {
        if (!empty($_FILES['file'])) {
            $fileName = $_FILES["file"]["name"];
            $fileTempName = $_FILES["file"]["tmp_name"];

            $imageUpload = new ResizeUploadImageUtility();
            $arrayResult = $imageUpload->createImages($fileTempName, $fileName, ConstantConfig::PATH_SECTION_IMAGES);

            echo json_encode($arrayResult);
        } else {
            echo json_encode(['status' => "danger", 'message' => "Please select an image to upload!"]);
        }
    }

    public function ajaxDeleteImageSectionMethod(): void
    {
        $file = $this->requestData('file');

        $methodUtility = new MethodsUtility();
        $deleteImages = $methodUtility->fileMultiDelete(
            [ConstantConfig::PATH_SECTION_IMAGES . 'photo/' . $file, ConstantConfig::PATH_SECTION_IMAGES . 'thumb/' . $file]
        );

        if ($deleteImages !== null) {
            echo json_encode(['status' => "danger", 'message' => $deleteImages]);
        } else {
            echo json_encode(['status' => "success", 'message' => "The image has been successfully deleted."]);
        }
    }
}
