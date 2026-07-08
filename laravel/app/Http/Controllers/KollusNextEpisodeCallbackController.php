<?php

namespace App\Http\Controllers;

use App\Http\Requests\KollusNextEpisodeCallbackRequest;
use App\Services\KollusNextEpisodeCallbackService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class KollusNextEpisodeCallbackController extends Controller
{
    public function __construct(
        private readonly KollusNextEpisodeCallbackService $callbackService,
    ) {}

    /**
     * Kollus 플레이어의 다음 회차 연속 재생 콜백을 처리한다.
     *
     * 응답 규격 (Kollus 문서 기준):
     *   - plain JSON { result, next_url }
     *   - next_url은 반드시 SI 링크(/si) 형태여야 함
     */
    public function handle(KollusNextEpisodeCallbackRequest $request): JsonResponse
    {
        try {
            $nextUrl = $this->callbackService->buildNextUrl(
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
