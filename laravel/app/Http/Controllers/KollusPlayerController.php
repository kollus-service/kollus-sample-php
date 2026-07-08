<?php

namespace App\Http\Controllers;

use App\Services\KollusJwtService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class KollusPlayerController extends Controller
{
    public function __construct(
        private readonly KollusJwtService $jwtService,
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
}
