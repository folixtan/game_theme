<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace FolixCode\ThirdPartyOrder\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\App\State;
use Psr\Log\LoggerInterface;
use FolixCode\ThirdPartyOrder\Model\MessageQueue\Consumer\OrderSyncConsumer;
use  Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Symfony\Component\Console\Input\InputOption;

class ProcessThirdPartyOrdersCommand extends Command
{
    public const COMMAND_NAME = 'folixcode:process:third-party-orders';

    /**
     * @var State
     */
    private $state;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderSyncConsumer
     */
    private $orderProcessor;


    private $operationFactory;

    /**
     * @param State $state
     * @param LoggerInterface $logger
     * @param OrderSyncConsumer $orderProcessor
     */
    public function __construct(
        State $state,
        LoggerInterface $logger,
        OperationInterfaceFactory $operationFactory,
        OrderSyncConsumer $orderProcessor
    ) {
        $this->state = $state;
        $this->logger = $logger;
        $this->orderProcessor = $orderProcessor;
        $this->operationFactory = $operationFactory;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDefinition([
                new InputOption(
                    'order_id',
                    'o',
                    InputOption::VALUE_REQUIRED,
                    'Order ID to process'
                )
            ])
            ->setDescription('Process third party orders');

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setDecorated(true);
        
        try {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }

        $output->writeln('<info>========================================</info>');
        $output->writeln('<info>Processing Third Party Orders...</info>');
        $output->writeln('<info>========================================</info>');
        
        try {
            $orderId = $input->getOption('order_id');
            $operation = $this->operationFactory->create(
               [
                'data' => [
                    'topic_name' => \FolixCode\ThirdPartyOrder\Model\MessageQueue\Publisher::TOPIC_ORDER_SYNC,
                    'serialized_data' => json_encode([
                        'order_id' => $orderId
                    ]),
                    'status' => 4
                ]
            ]
            );
             $this->orderProcessor->process($operation);
            //$output->writeln('<info>Successfully processed ' . $result . ' orders.</info>');
            
            $output->writeln('<info>========================================</info>');
            $output->writeln('<info>Third Party Orders Processing Completed!</info>');
            $output->writeln('<info>========================================</info>');
            
            return Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $this->logger->error('Error processing third party orders: ' . $e->getMessage());
            $output->writeln('<error>Error processing third party orders: ' . $e->getMessage() . '</error>');
            
            return Cli::RETURN_FAILURE;
        }
    }
}