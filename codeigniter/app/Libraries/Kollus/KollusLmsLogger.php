<?php

namespace App\Libraries\Kollus;

/**
 * Laravel의 전용 로그 채널(Log::channel('kollus_lms'))에 대응하는
 * writable/logs/kollus-lms.log 전용 기록기.
 */
class KollusLmsLogger
{
    private const LOG_FILE = WRITEPATH . 'logs/kollus-lms.log';

    /**
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void
    {
        $line = sprintf(
            '[%s] kollus_lms.INFO: %s %s%s',
            date('Y-m-d H:i:s'),
            $message,
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            PHP_EOL,
        );

        file_put_contents(self::LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    }
}
