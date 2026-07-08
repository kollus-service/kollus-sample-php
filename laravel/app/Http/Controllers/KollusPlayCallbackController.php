<?php

namespace App\Http\Controllers;

use App\Http\Requests\KollusPlayCallbackRequest;
use App\Services\KollusPlayCallbackService;
use Illuminate\Http\Response;
use RuntimeException;

class KollusPlayCallbackController extends Controller
{
    /**
     * 재생 차단 시 플레이어에 노출할 기본 메시지.
     * 서비스 정책에 맞게 변경하거나 DB/config에서 가져오도록 수정 가능.
     */
    private const DENY_MESSAGE =
        "해당 ID는 강의 공유, 양도, 불법거래 등 \n부정사용 행위 사유로 수강정지 조치되었습니다.";

    public function __construct(
        private readonly KollusPlayCallbackService $callbackService,
    ) {}

    /**
     * Kollus 플레이어로부터 콜백을 수신하고 JWT 응답을 반환한다.
     *
     * 응답 규격 (Kollus 문서 기준):
     *   - Content-Type : text/plain; charset=utf-8
     *   - X-KOLLUS-USERKEY : {CUSTOM_KEY}  (반드시 포함)
     *   - Body : JWT 인코딩된 JSON 응답
     */
    public function handle(KollusPlayCallbackRequest $request): Response
    {
        $kind = $request->integer('kind');

        // TODO: 실제 서비스에서는 client_user_id / media_content_key로 DB 조회 후 결정
        $isAllowed = true;

        try {
            $jwt = $this->callbackService->buildResponse(
                kind: $kind,
                isAllowed: $isAllowed,
                denyMessage: $isAllowed ? null : self::DENY_MESSAGE,
            );
        } catch (RuntimeException $e) {
            // JWT 생성 실패는 서버 설정 오류이므로 500 반환
            abort(500, 'Kollus 콜백 응답 생성에 실패했습니다: ' . $e->getMessage());
        }

        return response($jwt, Response::HTTP_OK)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('X-KOLLUS-USERKEY', config('kollus.custom_key'));
    }
}
