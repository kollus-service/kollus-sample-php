<?php
/**
 * Kollus 다음 회차 연속 재생 콜백(next-episode-callback)에 응답할 다음 회차 SI 링크를 생성하는 서비스.
 */

namespace App\Libraries\Kollus;

use RuntimeException;

class KollusNextEpisodeCallbackService
{
    private readonly KollusJwtService $_jwtService;

    private readonly string $_nextEpisodeMcks;

    /**
     * @param KollusJwtService $jwtService      SI 링크를 서명할 JWT 서비스 (kollusJwtService)
     * @param string           $nextEpisodeMcks 다음 회차 샘플 MCK 목록 — 콤마 구분 문자열 (config(Kollus::class)->nextEpisodeMcks)
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
