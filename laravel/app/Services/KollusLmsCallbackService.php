<?php
/**
 * Kollus LMS 콜백 payload(json_data)를 파싱해 시청 진도·구간 스킵 여부 등 로그용 요약 정보를 만드는 서비스.
 */

namespace App\Services;

class KollusLmsCallbackService
{
    /**
     * Kollus LMS 콜백 payload(json_data 디코딩 결과)를 파싱해
     * 진도율 체크에 필요한 요약 정보를 만든다.
     *
     * - 시청 세트: client_user_id + media_content_key 조합으로 식별
     *   (실제 서비스에서는 이 조합 기준으로 DB에 playtime/showtime을 누적 저장)
     * - 구간 이동(트리키 클릭) 감지: block_info의 b{n} 플래그 중 마지막으로
     *   시청 완료된 블록 이전에 미시청 구간이 남아있으면 구간을 건너뛴 것으로 판단
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed> 로그로 남길 요약 데이터
     */
    public function summarize(array $payload): array
    {
        $userInfo = $payload['user_info'] ?? [];
        $contentInfo = $payload['content_info'] ?? [];
        $blockInfo = $payload['block_info'] ?? [];

        $blocks = $this->_analyzeBlocks($blockInfo['blocks'] ?? []);

        return [
            'tracking_key' => sprintf(
                '%s:%s',
                $userInfo['client_user_id'] ?? 'unknown',
                $contentInfo['media_content_key'] ?? 'unknown',
            ),
            'client_user_id' => $userInfo['client_user_id'] ?? null,
            'player_id' => $userInfo['player_id'] ?? null,
            'device' => $userInfo['device'] ?? null,
            'media_content_key' => $contentInfo['media_content_key'] ?? null,
            'channel_key' => $contentInfo['channel_key'] ?? null,
            'duration' => (int) ($contentInfo['duration'] ?? 0),
            'showtime' => (int) ($contentInfo['showtime'] ?? 0),
            'playtime' => (int) ($contentInfo['playtime'] ?? 0),
            'playtime_percent' => (int) ($contentInfo['playtime_percent'] ?? 0),
            'total_block_count' => (int) ($blockInfo['block_count'] ?? 0),
            'watched_block_count' => $blocks['watchedCount'],
            'skip_detected' => $blocks['skipDetected'],
            'uservalues' => $payload['uservalues'] ?? [],
        ];
    }

    /**
     * block_info.blocks(b{n}/t{n}/p{n} 트리플)를 분석해 시청 블록 수와
     * 구간 이동(스킵) 여부를 계산한다.
     *
     * @param array<string, string> $blocks
     * @return array{watchedCount: int, skipDetected: bool}
     */
    private function _analyzeBlocks(array $blocks): array
    {
        $watchedFlags = [];

        foreach ($blocks as $key => $value) {
            if (preg_match('/^b(\d+)$/', (string) $key, $matches) === 1) {
                $watchedFlags[(int) $matches[1]] = (int) $value === 1;
            }
        }

        if ($watchedFlags === []) {
            return ['watchedCount' => 0, 'skipDetected' => false];
        }

        ksort($watchedFlags);

        $watchedIndexes = array_keys(array_filter($watchedFlags));
        $watchedCount = count($watchedIndexes);

        if ($watchedCount === 0) {
            return ['watchedCount' => 0, 'skipDetected' => false];
        }

        $lastWatchedIndex = max($watchedIndexes);

        // 마지막 시청 블록 이전 구간에 미시청 블록이 있으면 트리키 클릭으로 구간을 건너뛴 것으로 판단
        $skipDetected = false;
        foreach ($watchedFlags as $index => $isWatched) {
            if ($index >= $lastWatchedIndex) {
                break;
            }
            if (! $isWatched) {
                $skipDetected = true;
                break;
            }
        }

        return ['watchedCount' => $watchedCount, 'skipDetected' => $skipDetected];
    }
}
