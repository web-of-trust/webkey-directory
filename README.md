Webkey Directory
================
Webkey Directory is a public service for the discovery of OpenPGP-compatible keys

## Features
- Publish OpenPGP keys as a Web Key Directory

## Verifying Keyserver (VKS) Interface
Webkey Directory implements a subset of the VKS protocol.
Keys can be discovered by `Fingerprint`, `KeyID`, `Email Address`

* GET /vks/v1/by-fingerprint/<FINGERPRINT>
Retrieves the key with the given `Fingerprint`.
The Fingerprint may refer to the primary key, or any subkey.
Hexadecimal digits MUST be uppercase, and MUST NOT be prefixed with 0x.
The returned key is ASCII Armored, and has a content-type of `application/pgp-keys`.

* GET /vks/v1/by-keyid/<KEY-ID>
Retrieves the key with the given long `KeyID`.
The KeyID may refer to the primary key, or any subkey.
Hexadecimal digits MUST be uppercase, and MUST NOT be prefixed with 0x.
The returned key is ASCII Armored, and has a content-type of `application/pgp-keys`.

* GET /vks/v1/by-email/<URI-ENCODED EMAIL-ADDRESS>
Retrieves the key with the given `Email Address`. Only exact matches are accepted.
Lookup by email address requires opt-in by the owner of the email address.
The returned key is ASCII Armored, and has a content-type of `application/pgp-keys`.

## Licensing
[GNU Affero General Public License v3.0](LICENSE)

    For the full copyright and license information, please view the LICENSE
    file that was distributed with this source code.
