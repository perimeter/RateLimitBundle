<?php

/*
 * This file is part of the Perimeter package.
 *
 * (c) Adobe Systems, Inc. <bshafs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Perimeter\RateLimitBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
* 
*/
class MeterCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('perimeter:rate-limit-meter')
            ->setDescription('administer your meters')
            ->setHelp(<<<EOF
The <comment>%command.name%</comment> task creates, updates, and deletes meters according to your perimeter.rate_limit.storage.admin service

GET a meter: 
    <comment>%command.name%</comment> [meter_id]

CREATE or UPDATE a meter: 
    <comment>%command.name%</comment> [meter_id] [warn_threshold] [limit_threshold] ...

DELETE a meter
    <comment>%command.name%</comment> [meter_id] --delete

    <info>php %command.full_name%</info>
EOF
            )
            ->addArgument(
                'meter_id',
                InputArgument::REQUIRED,
                'the meter_id for the requested meter'
            )
            ->addArgument(
                'warn_threshold',
                InputArgument::OPTIONAL,
                'the number of tokens consumed before the warn header is emitted'
            )
            ->addArgument(
                'limit_threshold',
                InputArgument::OPTIONAL,
                'the number of tokens consumed before the request is considered over the rate limit'
            )
            ->addArgument(
                'should_warn',
                InputArgument::OPTIONAL,
                '(boolean) whether or not the meter should issue a rate limit warning when the warn threshold is reached',
                true
            )
            ->addArgument(
                'should_limit',
                InputArgument::OPTIONAL,
                '(boolean) whether or not the meter should return a rate limit response when the limit threshold is reached',
                true
            )
            ->addArgument(
                'num_tokens',
                InputArgument::OPTIONAL,
                'the number of tokens each call to this meter consumes',
                1
            )
            ->addArgument(
                'throttle_ms',
                InputArgument::OPTIONAL,
                'when this meter is over the limit, sleep for this number of milliseconds before returning a response',
                0
            )
            ->addOption(
                'delete',
                null,
                InputOption::VALUE_NONE,
                'wheter to register the autostart cron'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $storage = $this->getContainer()->get('perimeter.rate_limit.storage.admin');

        $meterId = $input->getArgument('meter_id');
        $meter = $storage->findOneByMeterId($meterId);

        // DELETE requests
        if ($input->getOption('delete')) {
            if (!$meter) {
                $output->writeln(sprintf('<error>cannot delete: meter %s does not exist</error>', $meterId));

                return 1;
            }

            $storage->deleteMeter($meterId);
            $output->writeln(sprintf('<info>meter %s deleted successfully</info>', $meterId));

            return;
        }

        // GET requests
        if (is_null($input->getArgument('warn_threshold'))) {
            if (!$meter) {
                $output->writeln(sprintf('<error>meter %s does not exist</error>', $meterId));

                return 1;
            }
        // UPDATE or CREATE requests
        } else {
            $meterData = array_merge((array) $meter, array_filter(array(
                'meter_id'          => $input->getArgument('meter_id'),
                'warn_threshold'    => $input->getArgument('warn_threshold'),
                'limit_threshold'   => $input->getArgument('limit_threshold'),
                'should_warn'       => $input->getArgument('should_warn'),
                'should_limit'      => $input->getArgument('should_limit'),
                'num_tokens'        => $input->getArgument('num_tokens'),
                'throttle_ms'       => $input->getArgument('throttle_ms'),
            ), 'Perimeter\RateLimitBundle\Controller\MeterApiController::filterNullValues'));

            // UPDATE
            if ($meter) {
                $meter = $storage->saveMeterData($meterData);
                $output->writeln(sprintf('<info>meter %s updated successfully!</info>', $meterId));
                $output->writeln('');
            // CREATE
            } else {
                $meter = $storage->addMeter($meterData);
                $output->writeln(sprintf('<info>meter %s created successfully!</info>', $meterId));
                $output->writeln('');
            }
        }

        // print all meter info for GET, UPDATE, and CREATE requests
        foreach ($meter as $key => $value) {
            if (!is_string($value)) {
                $value = var_export($value, true);
            }
            $output->writeln(sprintf('%s: <info>%s</info>', $key, $value));

        }
    }
}
