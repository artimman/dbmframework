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

namespace Dbm\Api;

interface ApiClientInterface
{
    public function request(string $method, string $endpoint, array $options = []): ApiResponse;

    public function get(string $endpoint, array $query = []): ApiResponse;

    public function post(string $endpoint, array $data = []): ApiResponse;

    public function put(string $endpoint, array $data = []): ApiResponse;

    public function delete(string $endpoint, array $data = []): ApiResponse;
}
