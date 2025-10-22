# DBMFramework

**Fast. Flexible. PSR-Compatible.**
**Modern PHP MVC/MVP Framework + CMS Engine**

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.1-blue)](http://php.net)
[![PSR](https://img.shields.io/badge/PSR-1%2C%204%2C%2011%2C%2012-green)](https://www.php-fig.org/)
[![Build](https://img.shields.io/badge/build-passing-success)]()
[![Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen)]()
[![Composer](https://img.shields.io/badge/composer-ready-orange)](https://getcomposer.org/)
[![Speed](https://img.shields.io/badge/performance-ultra%20fast-red)]()
[![License](https://img.shields.io/badge/license-DbM-orange)](https://dbm.org.pl)

DBM PHP MVC MVP Framework + DBM CMS, Version 4  
All copyrights reserved by Design by Malina (DbM)  
Website: [www.dbm.org.pl](http://www.dbm.org.pl)  

## About the Framework

DbM Framework is one of the fastest PHP solutions based on the MVC and MVP patterns, combining lightweight design, flexibility, and performance with modern extensibility. It allows for easy addition of features without interfering with the core, and its well-thought-out architecture ensures stability and security. It is an ideal choice for developers who value full control over their code and the freedom to create advanced web applications.

DbM CMS is a framework-based, turnkey solution for those who want to quickly launch a website or application without coding. It supports both simple websites and complex database-driven projects. If you don't have time to create your own modules, you can take advantage of ready-made tools for managing content, SEO, and site structure. An effective solution that accelerates project development without sacrificing the flexibility of a framework.

### DbM Framework is:
⚙️ **PSR (1, 4, 11, 12) Compliant** - Industry Standard Ready Code  
🔁 **REST API Routing** - Lightweight, Ready, Lightweight  
🧠 **Smart DI Container** - Manual or Semi-Automatic Dependency Injection  
🧱 **Composer & Autoload** - Ready to Use in Any Project  
🚀 **Ultra Fast View Engine 2.0** - Speed ​​Similar to Native PHP  
🧩 **DbM CMS** - Framework-Based Content Management System, Ready-Made Authentication and Administration Panel  

DbM is a framework that doesn't fight the developer - **it lets them work the way they want**.

## Framework Structure

- `application/` – framework core: classes, interfaces, libraries (+ Routing, DI, API)
- `config/` – configuration files (optional, e.g., php.ini, CMS modules)
- `frontend/` – frontend (optional React.js or Vue.js, Node.js, Webpack)
- `libraries/` – external libraries (PSR, PHPMailer, Guzzle)
- `public/` – public files (domain root)
- `src/` – application logic: controllers, services, models, services
- `templates/` – view templates
- `tests/` – unit tests
- `translations/` – translation files (optional)
- `var/` – cache and logs (automatically created, write permissions required)
- `vendor/` – libraries installed by Composer (automatically created)

## Additional Structure for CMS installations

- `data/` – data and files (write permissions required)
- `modules/` – content management system modules

## Manual Installation

1. Point your domain to the `public/` directory. Set the appropriate `RewriteBase` in the `public/.htaccess` file.
2. If you're using localhost, copy the `.htaccess` file from the `_Documents` directory to the root directory and adjust the `RewriteBase`.
3. Configure the `.env.example` file, then rename it to `.env`.

In the basic configuration, complete the **General settings** section:

```env
APP_URL="http://localhost/"
APP_NAME="Application Name"
APP_EMAIL="email@domain.com"
```

Next, configure: Cache settings, Database settings, Mailer settings, API settings.

**Note:** After launching the application, set CACHE_ENABLED=true to enable caching and speed up the page.

## Autoloading

Manual installation makes the framework independent of other tools, equipped with its own autoloading. Executing the `composer install` command will automate the framework, create a Composer autoloader, and install selected packages, such as email sending and development packages. After executing the command, the framework will work with Composer.

## Installing with Composer

If you prefer to install with Composer or your project requires additional packages:

```bash
git clone https://github.com/designbymalina/dbmframework.git
```

If you want to use external libraries, you can use Composer:

```bash
composer install
```

Installing with Composer will create autoloading and download all dependencies.

**Note:** After installing the application with Composer, the necessary dependencies will be available, and the `libraries` directory can be removed.

## Routing

Classic routing is defined in the file: `application/routes.php`.

Example:

```shell
$router->get('/path', [NameController::class, 'methodName'], 'route_name');
```

You define REST API Routing in the `application/api.php` file.

Example:

```shell
$router->get('/api/path', [NameApiController::class, 'methodName'], 'api_route_name');
```

## Dependency Injection

DbM Framework uses a lightweight DI container, compliant with **PSR-11**, which offers two modes of operation:

- **Manual configuration (recommended)**

You register all dependencies explicitly in the `application/services.php` file:

```php
$container->set(Database::class, fn() => new Database($config));
```

This mode guarantees full control over dependencies and performance.

- **Semi-automatic configuration (available)**

In many cases, the framework can recognize and inject a dependency based on the parameter type in the controller or service constructor:

```php
public function __construct(Mailer $mailer) { ... }
```

If the class is known and PSR-4 autoload-compliant, it will be injected correctly. However, explicit service registration is recommended for full predictability and stability.

This compromise combines the **simplicity** of manual DI with the **flexibility** of automatic detection—without the cost of full reflection found in heavy frameworks.

## Template Engine

The framework uses a built-in template engine by default. You can freely replace it with something like Twig.

Why use DbM View Engine over the most popular engines:

| Feature           | Twig    | Blade   | DbM View Engine             |
| ----------------- | ------- | ------- | --------------------------- |
| Speed             | average | good    | 🚀 fastest                  |
| PHP-friendly      | ❌       | ⚠️      | ✅ developer in full control |
| Filters           | yes     | yes     | ✅ simple and extensible     |
| Plugins           | complex | none    | ✅ runtime callbacks         |
| Block inheritance | yes     | yes     | ✅ + append/prepend support  |
| Cache             | yes     | yes     | ✅ OPC class cache           |
| Sandbox           | yes     | none    | ✅ optional                  |
| Dependencies      | heavy   | medium  | ✅ none                      |
| Size              | >400 KB | ~200 KB | ~50 KB                      |

In tests with CACHE=TRUE, the results achieved were similar to those of Native PHP.

=== TEMPLATE ENGINE BENCHMARK - benchmark.phtml ===

| MODE | AVG(ms) | MEDIAN | MIN | MAX | STD |
|------|---------|--------|-----|-----|-----|
| CACHE=FALSE | 1.31 | 1.29 | 1.17 | 1.67 | 0.09 |
| CACHE=TRUE | 0.17 | 0.16 | 0.16 | 0.31 | 0.02 |
| Native PHP | 0.15 | 0.14 | 0.14 | 0.18 | 0.01 |

**Conclusion**: DbM View Engine (cache=true) is almost as fast as pure PHP, confirming its performance.

Templates are located in the `templates/` directory.

## Command Console

A lightweight and fast CLI for CRON and DEV tasks. It provides a simple way to run background or maintenance tasks directly from the command line with a lightweight and self-contained implementation. Console commands are executed via the file: `application/console.php`.

## Additional Information

In a production environment, point your domain to the public/ directory. If running the application in a production environment (on a remote server), **you should point your domain to the `/public/` directory**, as this serves as the document root.

Make sure that open_basedir does not block access to directories. Additionally, depending on your server configuration, **you may need to disable the `open_basedir` restriction** in your PHP settings. This security measure, known as "page separation," can block access to certain directories and files outside the domain root, preventing the application from opening within the domain.

After launching the application, enable caching (`CACHE_ENABLED=true`) to speed up the page.

If you're using a CMS, ensure write permissions in data/ and modules/.

**IMPORTANT!** Please retain the footer: "Created with <a href="https://dbm.org.pl/" title="DbM">DbM Framework</a>". The link should remain intact. Thank you for supporting the project! By maintaining the link in the footer, you help develop the free, open-source framework and support its development and the community of independent PHP developers.

#### DOCUMENTATION:

[Application Programming Interface (api.php)](_Documents/Docs/api.md)  
[Command console (console.php)](_Documents/Docs/console.md)  
[Dependency Injection - DI (services.php)](_Documents/Docs/dependency-injection.md)  
[Environment configuration (.env)](_Documents/Docs/env.md)  
[Middleware (middleware.php)](_Documents/Docs/middleware.md)  
[Routing (routes.php)](_Documents/Docs/routing.md)  
[Request](_Documents/Docs/request.md)  
[Response](_Documents/Docs/response.md)  
[TemplateEngine](_Documents/Docs/template-engine.md)  
[TemplateFeature](_Documents/Docs/template-feature.md)  
[Templates](_Documents/Docs/templates.md)  
