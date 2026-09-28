<?php
/**
 * GET /kollus/player, /kollus/player/multi-drm — JWT 서명된 플레이어 URL을 생성해 iframe 뷰(kollus.player)를 렌더링하는 컨트롤러.
 */

namespace App\Http\Controllers;

use App\Services\KollusJwtService;
use App\Services\KollusMultiDrmService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class KollusPlayerController extends Controller
{
    /** 일반 재생용 JWT 생성 및 플레이어 URL 조립 서비스 */
    private readonly KollusJwtService $_jwtService;

    /** 멀티 DRM(inkaDRM/Pallycon) 재생용 플레이어 URL 조립 서비스 */
    private readonly KollusMultiDrmService $_multiDrmService;

    /**
     * @param KollusJwtService      $jwtService      일반 재생용 JWT 생성 및 플레이어 URL 조립 서비스
     * @param KollusMultiDrmService $multiDrmService 멀티 DRM(inkaDRM/Pallycon) 재생용 플레이어 URL 조립 서비스
     */
    public function __construct(KollusJwtService $jwtService, KollusMultiDrmService $multiDrmService)
    {
        $this->_jwtService      = $jwtService;
        $this->_multiDrmService = $multiDrmService;
    }

    /**
     * Kollus 플레이어 페이지를 렌더링한다.
     *
     * 쿼리 파라미터:
     *   - media_content_key (필수): 재생할 미디어 콘텐츠 키
     *
     * @param Request $request media_content_key 쿼리 파라미터를 담은 요청
     * @return View JWT 서명된 플레이어 URL을 iframe src로 넣은 kollus.player 뷰
     *
     * @throws \Illuminate\Validation\ValidationException media_content_key 누락 시
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException JWT 생성 실패 시 500 (abort)
     */
    public function show(Request $request): View
    {
        $validated = $request->validate([
            'media_content_key' => ['required', 'string']
        ]);

        // 실제 서비스에서는 auth 미들웨어로 보호하고, $clientUserId를 auth()->id() 또는 세션에서 가져오는 것을 권장.
        $clientUserId = 'guest';

        try {
            $playerUrl = $this->_jwtService->buildPlayerUrl(
                clientUserId: $clientUserId,
                mediaContentKeys: [$validated['media_content_key']],
            );
        } catch (RuntimeException $e) {
            // JWT 생성 실패는 서버 설정 문제이므로 500 응답
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Kollus 플레이어 URL 생성에 실패했습니다: ' . $e->getMessage());
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
     *
     * @param Request $request media_content_key / content_id 쿼리 파라미터를 담은 요청 (User-Agent로 DRM 종류 판별)
     * @return View 멀티 DRM 정책이 포함된 플레이어 URL을 iframe src로 넣은 kollus.player 뷰
     *
     * @throws \Illuminate\Validation\ValidationException media_content_key / content_id 누락 시
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException JWT 생성·라이선스 룰 암호화 실패 시 500 (abort)
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
            $playerUrl = $this->_multiDrmService->buildPlayerUrl(
                clientUserId: $clientUserId,
                mediaContentKey: $validated['media_content_key'],
                contentId: $validated['content_id'],
                userAgent: (string) $request->userAgent(),
            );
        } catch (RuntimeException $e) {
            // JWT/암호화 실패는 서버 설정 문제이므로 500 응답
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Kollus 멀티 DRM 플레이어 URL 생성에 실패했습니다: ' . $e->getMessage());
        }

        return view('kollus.player', [
            'playerUrl' => $playerUrl,
        ]);
    }
}
