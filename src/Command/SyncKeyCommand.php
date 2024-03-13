<?php declare(strict_types=1);
/**
 * This file is part of the Webkey Directory project.
 *
 * Licensed under GNU Affero General Public License v3.0
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wkd\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Wkd\Sync\SyncKey;

/**
 * Sync key command class
 * 
 * @package  Wkd
 * @category Command
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
#[AsCommand(
    name: 'webkey:sync',
    description: 'Sync OpenPGP public keys from webkey service.'
)]
class SyncKeyCommand extends Command
{
    private const WEBKEY_PRIVACY_URL_OPTION  = 'webkey-service-url';

    private ?string $webkeyPrivacyUrl;

    /**
     * Sync key command constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(
        private readonly ContainerInterface $container
    )
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!empty($this->webkeyPrivacyUrl)) {
            $sync = new SyncKey(
                $this->webkeyPrivacyUrl, $this->container
            );
            $sync->sync();
        }
        else {
            return $this->missingParameter($input, $output);
        }
        $output->writeln('Web keys successfully synced!');
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->addOption(
            self::WEBKEY_PRIVACY_URL_OPTION, null, InputOption::VALUE_REQUIRED, 'The webkey service url.'
        );
        $this->setHelp('This command allows you to sync OpenPGP public keys from webkey service.');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->webkeyPrivacyUrl = $input->getOption(self::WEBKEY_PRIVACY_URL_OPTION);

        $helper = $this->getHelper('question');
        if (empty($this->webkeyPrivacyUrl)) {
            $this->webkeyPrivacyUrl = $helper->ask(
                $input,
                $output,
                new Question('Please enter the webkey service url: '),
            );
        }
    }

    /**
     * Output missing parameter.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function missingParameter(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $style->error(sprintf(
            '%s parameter is missing!',
            self::WEBKEY_PRIVACY_URL_OPTION,
        ));
        return 1;
    }
}
