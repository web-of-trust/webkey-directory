<?php declare(strict_types=1);
/**
 * This file is part of the Webkey Directory project.
 *
 * Licensed under GNU Affero General Public License v3.0
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wkd\Controller;

use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface,
};

/**
 * Hkp controller class
 * HTTP Keyserver Protocol (HKP) Interface
 * https://datatracker.ietf.org/doc/html/draft-gallagher-openpgp-hkp
 * 
 * @package  App
 * @category Controller
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
class HkpController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    protected function action(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
    {
        return $response;
    }
}
