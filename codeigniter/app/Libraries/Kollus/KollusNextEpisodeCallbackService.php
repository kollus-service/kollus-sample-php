<?php

namespace App\Libraries\Kollus;

use RuntimeException;

class KollusNextEpisodeCallbackService
{
    /**
     * @param string $nextEpisodeMck 다음 회차 샘플 MCK (config(Kollus::class)->nextEpisodeMck)
     */
    public function __construct(
        private readonly KollusJwtService $jwtService,
        private readonly string $nextEpisodeMck,
    ) {
    }

    /**
     * 다음 회차 재생 URL을 생성한다. 다음 회차가 없으면 null을 반환한다.
     *
     * @param string $currentMck   현재 재생 중인 미디어 콘텐츠 키 (샘플에서는 미사용)
     * @param string $clientUserId 워터마킹/식별용 클라이언트 사용자 ID
     *
     * @throws RuntimeException JWT 생성 실패 시
     */
    public function buildNextUrl(string $currentMck, string $clientUserId): ?string
    {
        // 실제 서비스에서는 $this->nextEpisodeMck 대신 다음회차에 재생될 실제 미디어 콘텐츠 키를 DB 등에서 가져온다.
        return $this->jwtService->buildNextEpisodeUrl($clientUserId, $this->nextEpisodeMck);
    }
}
