<?php declare(strict_types=1);
/**
 * This file is part of the Webkey Directory project.
 *
 * Licensed under GNU Affero General Public License v3.0
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wkd\Enum;

/**
 * Policy enum
 * 
 * @package  Wkd
 * @category Enum
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
enum Policy: string
{
    case MailboxOnly = 'mailbox-only';
    case DaneOnly  = 'dane-only';
    case AuthSubmit  = 'auth-submit';
    case ProtocolVersion  = 'protocol-version';
    case SubmissionAddress  = 'submission-address';
}
