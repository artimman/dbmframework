<?php
/*
 * DbM Framework
 * All code copyright Design by Malina
 * DbM: www.dbm.org.pl
 */

declare(strict_types=1);

namespace App\Model;

use Dbm\Classes\DatabaseClass;

class BlogModel extends DatabaseClass // TODO! Remove extends DatabaseClass
{
    /* TODO! private $database;

    public function __construct(DatabaseClass $database)
    {
        $this->database = $database;
    } */

    public function getJoinArticlesLimit(int $limit): ?array
    {
        $query = "SELECT article.id AS aid, article.image_thumb, article.page_header, article.page_content, section.id AS sid, section.section_name, details.user_id AS uid, details.fullname"
            . " FROM dbm_article article"
            . " JOIN dbm_article_sections section ON section.id = article.section_id"
            . " JOIN dbm_user_details details ON details.user_id = article.user_id"
            . " ORDER BY article.created DESC LIMIT :limit";

        $this->queryExecute($query, [':limit' => $limit]);

        if ($this->rowCount() == 0) {
            return null;
        }

        return $this->fetchAllObject();
    }

    public function getJoinSectionArticles(int $id): ?array
    {
        $query = "SELECT article.id AS aid, article.image_thumb, article.page_header, article.page_content, section.id AS sid, section.section_name, details.user_id AS uid, details.fullname"
            . " FROM dbm_article article"
            . " JOIN dbm_article_sections section ON section.id = article.section_id"
            . " JOIN dbm_user_details details ON details.user_id = article.user_id"
            . " WHERE section.id = :id ORDER BY article.created DESC";

        $this->queryExecute($query, [':id' => $id]);

        if ($this->rowCount() == 0) {
            return null;
        }
        
        return $this->fetchAllObject();
    }

    public function getJoinArticle(int $id): ?object
    {
        $query = "SELECT article.id AS aid, article.page_header, article.page_content, article.meta_title, article.meta_description, article.meta_keywords"
            . ", section.id AS sid, section.section_name, details.user_id AS uid, details.fullname"
            . " FROM dbm_article article"
            . " JOIN dbm_article_sections section ON section.id = article.section_id"
            . " JOIN dbm_user_details details ON details.user_id = article.user_id"
            . " WHERE article.id = :id LIMIT 1";

        $this->queryExecute($query, [':id' => $id]);

        if ($this->rowCount() == 0) {
            return null;
        }

        return $this->fetchObject();
    }

    public function getSection(int $id): ?array
    {
        $query = "SELECT * FROM dbm_article_sections WHERE id = :id LIMIT 1";

        $this->queryExecute($query, [':id' => $id]);

        if ($this->rowCount() == 0) {
            return null;
        }

        return $this->fetch();
    }

    public function getSections(): ?array
    {
        $query = "SELECT * FROM dbm_article_sections ORDER BY id ASC";

        $this->queryExecute($query);

        if ($this->rowCount() == 0) {
            return null;
        }
        
        return $this->fetchAllObject();
    }
}
