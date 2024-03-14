Webkey Directory
================
Webkey Directory is a public service for discovery of OpenPGP-compatible keys

## Features
- Support parts of [Web Key Directory](https://datatracker.ietf.org/doc/draft-koch-openpgp-webkey-service) (WKD)
- Support parts of [HTTP Keyserver Protocol](https://datatracker.ietf.org/doc/html/draft-gallagher-openpgp-hkp) (HKP)
- Support parts of Verifying Keyserver (VKS)

### Web Key Directory (WKD) Interface
The Web Key Directory is a standard for discovery of OpenPGP keys by email address,
via the domain of its email provider. It is used to discover unknown keys in some email clients.

* `GET /.well-known/openpgpkey/<DOMAIN-PART>/hu/<LOCAL-PART-HASHED>`

Returns an ASCII Armored key matching the `DOMAIN-PART` and the `LOCAL-PART-HASHED`.

The `LOCAL-PART-HASHED` is hashed using the SHA-1 algorithm.
The resulting 160 bit digest is encoded using the Z-Base-32 method as described in [RFC6189], section 5.1.6.
The resulting string has a fixed length of 32 octets.

### HTTP Keyserver Protocol (HKP) Interface
Webkey Directory implements a subset of the HTTP Keyserver protocol so that OpenPGP tools can discover keys

* `GET /pks/lookup?op=get&search=<QUERY>`

Returns an ASCII Armored key matching the query. Query may be:
* An exact email address query of the form `localpart@example.org`.
* A hexadecimal representation of a long KeyID (e.g., `5A28D96A75CB054F`, optionally prefixed by `0x`).
* A hexadecimal representation of a Fingerprint (e.g., `6FFAD46F1A77B1C37D3B4AFC5E088B143FDA2105`,
  optionally prefixed by `0x`).

### Verifying Keyserver (VKS) Interface
Webkey Directory implements a subset of the VKS.
Keys can be discovered by `Fingerprint`, `KeyID`, `Email Address`

* `GET /vks/v1/by-fingerprint/<FINGERPRINT>`

Retrieves the key with the given `Fingerprint`.
The `Fingerprint` may refer to the primary key and MUST NOT be prefixed with `0x`.
The returned key is ASCII Armored, and has a content-type of `application/pgp-keys`.

* `GET /vks/v1/by-keyid/<KEY-ID>`

Retrieves the key with the given long `KeyID`.
The `KeyID` may refer to the primary key and MUST NOT be prefixed with `0x`.
The returned key is ASCII Armored, and has a content-type of `application/pgp-keys`.

* `GET /vks/v1/by-email/<URI-ENCODED-EMAIL-ADDRESS>`

Retrieves the key with the given `Email Address`. Only exact matches are accepted.
Lookup by email address requires opt-in by the owner of the email address.
The returned key is ASCII Armored, and has a content-type of `application/pgp-keys`.

## Installation
### System Requirements
* Web server with URL rewriting
* PHP 8.1 or newer

### Install Composer
Don’t have Composer? It’s easy to install by following the instructions on their [download](https://getcomposer.org/download) page.

### Install dependencies
```sh
composer install --optimize-autoloader --no-dev --prefer-dist
```

### Install a PSR-7 Implementation and ServerRequest Creator
Slim PSR-7
```sh
composer require slim/psr7
```

Nyholm PSR-7 and Nyholm PSR-7 Server
```sh
composer require nyholm/psr7 nyholm/psr7-server
```

Guzzle PSR-7
```sh
composer require guzzlehttp/psr7
```

Laminas Diactoros
```sh
composer require laminas/laminas-diactoros
```

### Copy over env configuration.
```sh
cp env .env
```
Environment variables:
| Name                  | Description                                 | Default          |
|-----------------------|---------------------------------------------|------------------|
| APP_NAME              | Application name                            | Webkey Directory |
| APP_VERSION           | Application version                         | 1.0.0            |
| ERROR_DISPLAY_DETAILS | Whether or not to display the error details | false            |
| ERROR_LOG             | Whether or not to log errors                | true             |
| ERROR_LOG_DETAILS     | Whether or not to log error details         | true             |

### Web Servers

#### Nginx web server
This is an example Nginx virtual host configuration for the domain example.com.
It listens for inbound HTTP connections on port 80. It assumes a PHP-FPM server is running on port 9123.
You should update the server_name, error_log, access_log, and root directives with your own values.
The root directive is the path to your application’s public document root directory.
```nginx
server {
    listen 80;
    server_name example.com;
    index index.php;
    error_log /path/to/example.error.log;
    access_log /path/to/example.access.log;
    root /path/to/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ \.php {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
        fastcgi_index index.php;
        fastcgi_pass 127.0.0.1:9123;
    }
}
```

#### Other Web Servers
For other Web Servers see [Slim 4 Documentation](https://www.slimframework.com/docs/v4/start/web-servers.html)

## OpenPGP Keys Synchronization
Webkey Directory allow to synchronize OpenPGP Keys from webkey service which provided by (Webkey Privacy)[https://github.com/web-of-trust/webkey-privacy]

This function using [PSR-17: HTTP Factories](https://www.php-fig.org/psr/psr-17/), [PSR-18: HTTP Client](https://www.php-fig.org/psr/psr-18/) for requesting to webkey service.
Make sure to install package(s) providing ["http client implementation"](https://packagist.org/providers/psr/http-client-implementation) & ["http factory implementation"](https://packagist.org/providers/psr/http-factory-implementation).
The recommended package is [Guzzle](https://docs.guzzlephp.org) which provide both PSR-17 & PSR-18.

```bash
composer require guzzlehttp/guzzle
```

Run `bin/console webkey:sync` command with parameter `--wks-url` to synchronize keys.

Example: To synchronize keys which is host at https://webkey.example.org
```bash
bin/console webkey:sync --wks-url=https://webkey.example.org/api/v1/certificate
```

## Licensing
[GNU Affero General Public License v3.0](LICENSE)

    For the full copyright and license information, please view the LICENSE
    file that was distributed with this source code.
