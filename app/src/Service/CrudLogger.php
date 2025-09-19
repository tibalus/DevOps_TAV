<?php
// src/Service/CrudLogger.php
namespace App\Service;

use Psr\Log\LoggerInterface;

class CrudLogger
{
    public function __construct(private LoggerInterface $logger) {}

    public function log(string $level, string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
