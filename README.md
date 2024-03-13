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

### HTTP Keyserver Protocol (HKP) Interface
Webkey Directory implements a subset of the HTTP Keyserver protocol so that OpenPGP tools can discover keys
* `GET /pks/lookup?op=get&search=<QUERY>`

Returns an ASCII Armored key matching the query. Query may be:
* An exact email address query of the form `localpart@example.org`.
* A hexadecimal representation of a long KeyID (e.g., `069C0C348DD82C19`, optionally prefixed by 0x).
* A hexadecimal representation of a Fingerprint (e.g., `8E8C33FA4626337976D97978069C0C348DD82C19`,
  optionally prefixed by 0x).

### Verifying Keyserver (VKS) Interface
Webkey Directory implements a subset of the VKS.
Keys can be discovered by `Fingerprint`, `KeyID`, `Email Address`

* `GET /vks/v1/by-fingerprint/<FINGERPRINT>`

Retrieves the key with the given `Fingerprint`.
The Fingerprint may refer to the primary key, or any subkey.
Hexadecimal digits MUST be uppercase, and MUST NOT be prefixed with 0x.
The returned key is ASCII Armored, and has a content-type of `application/pgp-keys`.

* `GET /vks/v1/by-keyid/<KEY-ID>`

Retrieves the key with the given long `KeyID`.
The KeyID may refer to the primary key, or any subkey.
Hexadecimal digits MUST be uppercase, and MUST NOT be prefixed with 0x.
The returned key is ASCII Armored, and has a content-type of `application/pgp-keys`.

* `GET /vks/v1/by-email/<URI-ENCODED EMAIL-ADDRESS>`

Retrieves the key with the given `Email Address`. Only exact matches are accepted.
Lookup by email address requires opt-in by the owner of the email address.
The returned key is ASCII Armored, and has a content-type of `application/pgp-keys`.

## Licensing
[GNU Affero General Public License v3.0](LICENSE)

    For the full copyright and license information, please view the LICENSE
    file that was distributed with this source code.
