# DbM Framework

DbM Framework PHP MVC + DbM CMS, Version 3  
All copyrights reserved by Design by Malina (DbM)  
Website: [www.dbm.org.pl](http://www.dbm.org.pl)  
Application requirements: [PHP](http://php.net) (>= 8.1), [MySQL](https://www.mysql.com)  

## About the framework

DbM Framework is one of the fastest PHP solutions based on the MVC pattern, combining lightness, flexibility and efficiency with modern expansion possibilities. It allows for easy addition of functions without interfering with the core, and the well-thought-out architecture ensures stability and security. It is an ideal choice for programmers who value full control over the code and freedom in creating advanced web applications.

DbM CMS is a ready-made framework-based solution for those who want to quickly launch a website or application without having to code. It supports both simple pages and complex database-driven projects. If you don't have time to create your own modules, you can use ready-made tools for managing content, SEO and site structure. An effective solution that speeds up project development without sacrificing the flexibility of the framework.

## Framework Structure

- `application/` – framework core: classes, interfaces, libraries
- `config/` – configuration files (optional, e.g. php.ini, CMS modules)
- `public/` – public files (domain root)
- `src/` – application logic: controllers, services, models, services
- `templates/` – view templates
- `tests/` – unit tests
- `translations/` – translation files (optional)
- `var/` – cache and logs (created automatically, write permissions required)
- `vendor/` – libraries installed by Composer

## Additional structure for CMS installation

- `data/` – data and files (write permissions required)
- `libraries/` – external libraries ([PHPMailer](https://github.com/PHPMailer/PHPMailer) - Sending library e-mails)
- `modules/` - content management system modules

## Manual installation

1. Point the domain to the `public/` directory. In the `public/.htaccess` file, set the appropriate `RewriteBase`.
2. If you are using localhost, copy the `.htaccess` file from the `_Documents` directory to the root directory and adjust the `RewriteBase`.
3. Configure the `.env.example` file, then rename it to `.env`.

In the basic configuration, complete the **General settings** section:

```env
APP_URL="http://localhost/"
APP_NAME="Application Name"
APP_EMAIL="email@domain.com"
```

Then configure: Cache settings, Database settings, Mailer settings.

**Note:** After running the application, set CACHE_ENABLED=true to enable caching and speed up the page.

## Autoloading

Manual installation makes the framework independent from other tools, equipped with its own autoloading. Executing the command `composer install` will automate the framework, create the Composer autoloading and install selected packages, e.g. for sending emails and development packages. After executing the command, the framework will work with Composer.

## Installation via Composer

If you prefer to install via Composer or your project requires additional packages:

```bash
git clone https://github.com/designbymalina/dbmframework.git
```

If you want to use external libraries, you can use Composer:

```bash
composer install
```

Installing via Composer will create the autoloading and download all dependencies.

## Routing

You define routing in the file: `application/routes.php`:

Example:

```shell
$router->addRoute('path', [NameController::class, 'methodName'], 'route_name');
```

## Dependency Injection

You register all application dependencies manually in the file: `application/services.php`. Registered classes can be automatically injected into controllers via the constructor or method parameters.

## Template engine

The framework uses the built-in template engine by default. You can freely replace it with e.g. Twig.

Templates are located in the `templates/` directory.

## Additional information

In a production environment, point the domain to the public/ directory. If you are running the application in a production environment (on a remote server), **you should point the domain to the `/public/`** directory, because it is the root directory (document root).

Make sure that open_basedir does not block access to directories. Additionally, depending on the server configuration, **it may be necessary to disable the `open_basedir`** restriction in PHP settings. This protection, known as "page separation", may block access to some directories and files outside the domain root directory, which will prevent the application from opening in the domain.

After running the application, enable the cache (`CACHE_ENABLED=true`), which will speed up the page.

If you are using a CMS, take care of write permissions in data/, modules/.

**IMPORTANT!** When using DbM Framework, add to the page (e.g. in the footer): "Created with <a href="https://dbm.org.pl/" title="DbM">DbM Framework</a>". The link should remain

Documentation:

[Environment configuration (.env)](_Documents/Docs/env.md)  
[Routing (routes.php)](_Documents/Docs/routing.md)  
[Dependency Injection (services.php)](_Documents/Docs/dependency-injection.md)  
[TemplateFeature](_Documents/Docs/template-feature.md)  
[Templates](_Documents/Docs/templates.md)  
