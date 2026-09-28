<?php
/**
 * Kollus LMS 콜백 요약 데이터를 writable/logs/kollus-lms.log에 기록하는 전용 로거.
 */

namespace App\Libraries\Kollus;

/**
 * Laravel의 전용 로그 채널(Log::channel('kollus_lms'))에 대응하는
 * writable/logs/kollus-lms.log 전용 기록기.
 */
class KollusLmsLogger
{
    /** 전용 로그 파일 경로 — 프레임워크 log_message()와 분리해 LMS 이력만 별도 파일로 모은다 */
    private const LOG_FILE = WRITEPATH . 'logs/kollus-lms.log';

    /**
     * INFO 레벨 한 줄을 전용 로그 파일에 append 한다.
     *
     * @param string               $message 로그 메시지 (예: 'lms_callback')
     * @param array<string, mixed> $context JSON으로 직렬화해 함께 기록할 요약 데이터
     * @return void
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
