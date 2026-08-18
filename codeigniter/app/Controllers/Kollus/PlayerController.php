<?php

namespace App\Controllers\Kollus;

use App\Controllers\BaseController;
use RuntimeException;

class PlayerController extends BaseController
{
    /**
     * Kollus 플레이어 페이지를 렌더링한다.
     *
     * 쿼리 파라미터:
     *   - media_content_key (필수): 재생할 미디어 콘텐츠 키
     */
    public function show()
    {
        if (! $this->validate([
            'media_content_key' => 'required|string',
        ])) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $mediaContentKey = (string) $this->request->getVar('media_content_key');

        // 실제 서비스에서는 auth 필터로 보호하고, $clientUserId를 인증된 사용자 식별자로 가져오는 것을 권장.
        $clientUserId = 'guest';

        try {
            $playerUrl = service('kollusJwtService')->buildPlayerUrl($clientUserId, [$mediaContentKey]);
        } catch (RuntimeException $e) {
            // JWT 생성 실패는 서버 설정 문제이므로 500 응답
            return $this->response->setStatusCode(500)
                ->setBody('Kollus 플레이어 URL 생성에 실패했습니다: ' . $e->getMessage());
        }

        return view('kollus/player', ['playerUrl' => $playerUrl]);
    }

    /**
     * 멀티 DRM(Widevine/PlayReady/FairPlay) 재생용 플레이어 페이지를 렌더링한다.
     *
     * 쿼리 파라미터:
     *   - media_content_key (필수): 재생할 미디어 콘텐츠 키
     *   - content_id (필수): 멀티 DRM 콘텐츠 ID (Kollus 업로드 파일 키)
     */
    public function showMultiDrm()
    {
        if (! $this->validate([
            'media_content_key' => 'required|string',
            'content_id'        => 'required|string',
        ])) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        // 실제 서비스에서는 auth 필터로 보호하고, $clientUserId를 인증된 사용자 식별자로 가져오는 것을 권장.
        $clientUserId = 'guest';

        try {
            $playerUrl = service('kollusMultiDrmService')->buildPlayerUrl(
                $clientUserId,
                (string) $this->request->getVar('media_content_key'),
                (string) $this->request->getVar('content_id'),
                $this->request->getUserAgent()->getAgentString(),
            );
        } catch (RuntimeException $e) {
            // JWT/암호화 실패는 서버 설정 문제이므로 500 응답
            return $this->response->setStatusCode(500)
                ->setBody('Kollus 멀티 DRM 플레이어 URL 생성에 실패했습니다: ' . $e->getMessage());
        }

        return view('kollus/player', ['playerUrl' => $playerUrl]);
    }
}
