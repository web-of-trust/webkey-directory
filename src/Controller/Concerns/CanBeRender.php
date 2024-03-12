<?php declare(strict_types=1);
/**
 * This file is part of the Webkey Directory project.
 *
 * Licensed under GNU Affero General Public License v3.0
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wkd\Controller\Concerns;

use Psr\Http\Message\ResponseInterface as Response;

/**
 * Can be render trait
 * 
 * @package  Wkd
 * @category Controller
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
trait CanBeRender
{
    /**
     * Render the template and write it to the response.
     *
     * @param Response $response
     * @param string   $template
     * @param array    $data
     *
     * @return Response
     */
    protected function render(
        Response $response, string $template, array $data = []
    ): Response
    {
        return $this->renderer->render(
            $response, $template, $data
        );
    }
}
