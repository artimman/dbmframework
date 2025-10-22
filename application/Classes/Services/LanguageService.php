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

namespace Dbm\Classes\Services;

use Dbm\Classes\Helpers\LanguageHelper;
use Dbm\Classes\Http\Request;
use Dbm\Classes\Manager\CookieManager;

class LanguageService
{
    private Request $request;
    private CookieManager $cookie;

    public function __construct(Request $request, CookieManager $cookie)
    {
        $this->request = $request;
        $this->cookie = $cookie;
    }

    /**
     * Get language code or null
     */
    public function detectLanguage(): ?string
    {
        $cookieLang = 'dbmLanguage';
        $defaultLanguage = LanguageHelper::getDefaultLanguage();

        $getLang = $this->request->getQuery('lang');

        if ($getLang !== null) {
            $getLang = strtolower(trim($getLang));

            if ($getLang === 'off') {
                $this->cookie->unsetCookie($cookieLang);
                return $defaultLanguage;
            }

            if (LanguageHelper::isSupported(strtoupper($getLang))) {
                $this->cookie->setCookie($cookieLang, $getLang);
                return $getLang;
            }

            return $defaultLanguage;
        }

        $cookieValue = $this->cookie->getCookie($cookieLang);
        if ($cookieValue !== null && LanguageHelper::isSupported(strtoupper($cookieValue))) {
            return $cookieValue;
        }

        return $defaultLanguage;
    }
}
