<?php

declare(strict_types=1);

namespace App\Config;

class ConstantConfig
{
    public const BLOG_INDEX_ITEM_LIMIT = 4; // number of elements on the blog home page
    public const GALLERY_INDEX_ITEM_LIMIT = 9; // number of images on gallery, view start LIMIT x 2 (6x2)

    public const PATH_PAGE_IMAGES = '../public/images/page/'; // image path for pages on text files
    public const PATH_BLOG_IMAGES = '../public/images/blog/'; // image path for blog (database system)
    public const PATH_SECTION_IMAGES = '../public/images/blog/category/'; // image path for blog category
    public const PATH_GALLERY_IMAGES = '../public/images/gallery/'; // image path for photo gallery
}
