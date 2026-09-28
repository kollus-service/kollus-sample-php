<?php
/**
 * POST /kollus/next-episode-callback — 다음 회차 연속 재생 콜백을 수신해 { result, next_url } JSON을 반환하는 컨트롤러.
 */

namespace App\Http\Controllers;

use App\Http\Requests\KollusNextEpisodeCallbackRequest;
use App\Services\KollusNextEpisodeCallbackService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class KollusNextEpisodeCallbackController extends Controller
{
    /** 다음 회차 SI 링크(next_url) 조립 서비스 */
    private readonly KollusNextEpisodeCallbackService $_callbackService;

    /**
     * @param KollusNextEpisodeCallbackService $callbackService 다음 회차 SI 링크(next_url) 조립 서비스
     */
    public function __construct(KollusNextEpisodeCallbackService $callbackService)
    {
        $this->_callbackService = $callbackService;
    }

    /**
     * Kollus 플레이어의 다음 회차 연속 재생 콜백을 처리한다.
     *
     * 응답 규격 (Kollus 문서 기준):
     *   - plain JSON { result, next_url }
     *   - next_url은 반드시 SI 링크(/si) 형태여야 함
     *
     * @param KollusNextEpisodeCallbackRequest $request mck(현재 재생 중인 MCK) / id(client_user_id)가 검증된 콜백 요청
     * @return JsonResponse { result: 1, next_url } 또는 다음 회차 없음·토큰 생성 실패 시 { result: 0 }
     */
    public function handle(KollusNextEpisodeCallbackRequest $request): JsonResponse
    {
        try {
            $nextUrl = $this->_callbackService->buildNextUrl(
                currentMck: $request->string('mck')->toString(),
                clientUserId: $request->input('id') ?? 'unknown',
            );
        } catch (RuntimeException $e) {
            // 토큰 생성 실패: 결과 0으로 응답하여 플레이어가 연속 재생을 중단하도록 처리
            report($e);

            return response()->json(['result' => 0]);
        }

        if ($nextUrl === null) {
            return response()->json(['result' => 0]);
        }

        return response()->json(['result' => 1, 'next_url' => $nextUrl]);
    }
}
