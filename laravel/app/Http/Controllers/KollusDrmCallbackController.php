<?php
/**
 * POST /kollus/drm-callback — Kollus DRM 다운로드 콜백을 수신해 JWT 응답(text/plain + X-KOLLUS-USERKEY)을 반환하는 컨트롤러.
 */

namespace App\Http\Controllers;

use App\Http\Requests\KollusDrmCallbackRequest;
use App\Services\KollusDrmCallbackService;
use Illuminate\Http\Response;
use RuntimeException;

class KollusDrmCallbackController extends Controller
{
    /** DRM 콜백 응답(JWT) 조립 서비스 */
    private readonly KollusDrmCallbackService $_callbackService;

    /**
     * @param KollusDrmCallbackService $callbackService DRM 콜백 응답(JWT) 조립 서비스
     */
    public function __construct(KollusDrmCallbackService $callbackService)
    {
        $this->_callbackService = $callbackService;
    }

    /**
     * Kollus 플레이어로부터 DRM 다운로드 콜백을 수신하고 JWT 응답을 반환한다.
     *
     * 응답 규격 (Kollus 문서 기준):
     *   - Content-Type : text/plain; charset=utf-8
     *   - X-KOLLUS-USERKEY : {CUSTOM_KEY}  (반드시 포함)
     *   - Body : JWT 인코딩된 JSON 응답 ({ "data": [...] })
     *
     * @param KollusDrmCallbackRequest $request items(JSON 배열)가 검증·파싱된 DRM 콜백 요청
     * @return Response text/plain 본문에 JWT를 담은 응답
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException JWT 응답 생성 실패 시 500 (abort)
     */
    public function handle(KollusDrmCallbackRequest $request): Response
    {
        // prepareForValidation에서 이미 파싱된 배열
        $items = $request->validated()['items'];

        try {
            $jwt = $this->_callbackService->buildResponse($items);
        } catch (RuntimeException $e) {
            // JWT 인코딩 실패는 서버 설정 오류이므로 500 반환
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Kollus DRM 콜백 응답 생성에 실패했습니다: ' . $e->getMessage());
        }

        return response($jwt, Response::HTTP_OK)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('X-KOLLUS-USERKEY', config('kollus.custom_key'));
    }
}
