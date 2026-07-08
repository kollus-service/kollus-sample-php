<?php

namespace App\Http\Controllers;

use App\Http\Requests\KollusDrmCallbackRequest;
use App\Services\KollusDrmCallbackService;
use Illuminate\Http\Response;
use RuntimeException;

class KollusDrmCallbackController extends Controller
{
    public function __construct(
        private readonly KollusDrmCallbackService $callbackService,
    ) {}

    /**
     * Kollus 플레이어로부터 DRM 다운로드 콜백을 수신하고 JWT 응답을 반환한다.
     *
     * 응답 규격 (Kollus 문서 기준):
     *   - Content-Type : text/plain; charset=utf-8
     *   - X-KOLLUS-USERKEY : {CUSTOM_KEY}  (반드시 포함)
     *   - Body : JWT 인코딩된 JSON 응답 ({ "data": [...] })
     */
    public function handle(KollusDrmCallbackRequest $request): Response
    {
        // prepareForValidation에서 이미 파싱된 배열
        $items = $request->validated()['items'];

        try {
            $jwt = $this->callbackService->buildResponse($items);
        } catch (RuntimeException $e) {
            // JWT 인코딩 실패는 서버 설정 오류이므로 500 반환
            abort(500, 'Kollus DRM 콜백 응답 생성에 실패했습니다: ' . $e->getMessage());
        }

        return response($jwt, Response::HTTP_OK)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('X-KOLLUS-USERKEY', config('kollus.custom_key'));
    }
}
