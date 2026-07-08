<?php

namespace App\Http\Controllers;

use App\Services\KollusJwtService;
use App\Services\KollusMultiDrmService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class KollusPlayerController extends Controller
{
    public function __construct(
        private readonly KollusJwtService $jwtService,
        private readonly KollusMultiDrmService $multiDrmService,
    ) {}

    /**
     * Kollus 플레이어 페이지를 렌더링한다.
     *
     * 쿼리 파라미터:
     *   - media_content_key (필수): 재생할 미디어 콘텐츠 키
     */
    public function show(Request $request): View
    {
        $validated = $request->validate([
            'media_content_key' => ['required', 'string']
        ]);

        // 실제 서비스에서는 auth 미들웨어로 보호하고, $clientUserId를 auth()->id() 또는 세션에서 가져오는 것을 권장.
        $clientUserId = 'guest';

        try {
            $playerUrl = $this->jwtService->buildPlayerUrl(
                clientUserId: $clientUserId,
                mediaContentKeys: [$validated['media_content_key']],
            );
        } catch (RuntimeException $e) {
            // JWT 생성 실패는 서버 설정 문제이므로 500 응답
            abort(500, 'Kollus 플레이어 URL 생성에 실패했습니다: ' . $e->getMessage());
        }

        return view('kollus.player', [
            'playerUrl' => $playerUrl,
        ]);
    }

    /**
     * 멀티 DRM(Widevine/PlayReady/FairPlay) 재생용 플레이어 페이지를 렌더링한다.
     *
     * 쿼리 파라미터:
     *   - media_content_key (필수): 재생할 미디어 콘텐츠 키
     *   - content_id (필수): 멀티 DRM 콘텐츠 ID (Kollus 업로드 파일 키)
     */
    public function showMultiDrm(Request $request): View
    {
        $validated = $request->validate([
            'media_content_key' => ['required', 'string'],
            'content_id'        => ['required', 'string'],
        ]);

        // 실제 서비스에서는 auth 미들웨어로 보호하고, $clientUserId를 auth()->id() 또는 세션에서 가져오는 것을 권장.
        $clientUserId = 'guest';

        try {
            $playerUrl = $this->multiDrmService->buildPlayerUrl(
                clientUserId: $clientUserId,
                mediaContentKey: $validated['media_content_key'],
                contentId: $validated['content_id'],
                userAgent: (string) $request->userAgent(),
            );
        } catch (RuntimeException $e) {
            // JWT/암호화 실패는 서버 설정 문제이므로 500 응답
            abort(500, 'Kollus 멀티 DRM 플레이어 URL 생성에 실패했습니다: ' . $e->getMessage());
        }

        return view('kollus.player', [
            'playerUrl' => $playerUrl,
        ]);
    }
}
