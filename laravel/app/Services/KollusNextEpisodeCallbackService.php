<?php
/**
 * 다음 회차 연속 재생 콜백 응답용 SI 링크(next_url)를 조립하는 서비스.
 * 샘플에서는 config(kollus.next_episode_mcks)의 MCK 목록을 사용한다.
 */

namespace App\Services;

use RuntimeException;

class KollusNextEpisodeCallbackService
{
    /** SI 링크(다음 회차 URL) 생성에 사용하는 JWT 서비스 */
    private readonly KollusJwtService $_jwtService;

    /** 다음 회차 샘플 MCK 목록 원문 (콤마 구분 문자열, config('kollus.next_episode_mcks')) */
    private readonly string $_nextEpisodeMcks;

    /**
     * @param KollusJwtService $jwtService      SI 링크 생성용 JWT 서비스
     * @param string           $nextEpisodeMcks 다음 회차 샘플 MCK 목록 (콤마 구분 문자열, config('kollus.next_episode_mcks'))
     */
    public function __construct(KollusJwtService $jwtService, string $nextEpisodeMcks)
    {
        $this->_jwtService      = $jwtService;
        $this->_nextEpisodeMcks = $nextEpisodeMcks;
    }

    /**
     * 다음 회차 재생 URL을 생성한다. 다음 회차가 없으면 null을 반환한다.
     *
     * @param string $currentMck   현재 재생 중인 미디어 콘텐츠 키 (샘플 목록에서 다음 키를 고르는 기준)
     * @param string $clientUserId 워터마킹/식별용 클라이언트 사용자 ID
     * @return string|null         다음 회차 SI 링크, 설정된 MCK 목록이 비어 있으면 null
     *
     * @throws RuntimeException JWT 생성 실패 시
     */
    public function buildNextUrl(string $currentMck, string $clientUserId): ?string
    {
        // 실제 서비스에서는 설정 목록 대신 currentMck 다음 회차의 미디어 콘텐츠 키를 DB 등에서 가져온다.
        $nextMck = $this->_resolveNextMck($currentMck);
        if ($nextMck === null) {
            return null;
        }

        return $this->_jwtService->buildNextEpisodeUrl($clientUserId, $nextMck);
    }

    /**
     * 콤마 구분 샘플 MCK 목록에서 다음 회차 키를 고른다.
     * 현재 키가 목록에 있으면 그 다음 키, 목록에 없으면 첫 키를 돌려주고 목록이 비어 있으면 null.
     *
     * @param string $currentMck 현재 재생 중인 미디어 콘텐츠 키
     * @return string|null       다음 회차 MCK
     */
    private function _resolveNextMck(string $currentMck): ?string
    {
        $mcks = array_values(array_filter(
            array_map('trim', explode(',', $this->_nextEpisodeMcks)),
            static fn (string $mck): bool => $mck !== '',
        ));
        if ($mcks === []) {
            return null;
        }

        $index = array_search($currentMck, $mcks, true);

        // 마지막 회차 뒤에는 첫 회차로 돌아가 샘플 재생이 계속 이어지게 한다
        return $mcks[$index === false ? 0 : ($index + 1) % count($mcks)];
    }
}
