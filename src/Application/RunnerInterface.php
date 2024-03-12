<?php declare(strict_types=1);
/**
 * This file is part of the Webkey Directory project.
 *
 * Licensed under GNU Affero General Public License v3.0
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wkd\Application;

use Psr\Container\ContainerInterface;

/**
 * Runner interface
 * Runs an application hiding initialization details.
 *
 * @package  Wkd
 * @category Application
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
interface RunnerInterface
{
    /**
     * Get psr container
     *
     * @return ContainerInterface
     */
    function getContainer(): ContainerInterface;

    /**
     * Runs an application.
     *
     * @return void
     */
    function run(): void;
}
