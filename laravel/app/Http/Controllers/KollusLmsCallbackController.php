<?php

namespace App\Http\Controllers;

use App\Services\KollusLmsCallbackService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class KollusLmsCallbackController extends Controller
{
    public function __construct(
        private readonly KollusLmsCallbackService $callbackService,
    ) {}

    /**
     * Kollus LMS 콜백을 수신하여 시청 이력/학습 진도 데이터를 로그로 기록한다.
     *
     * 요청 바디는 $_POST['json_data']에 JSON 문자열로 전달됨 (Kollus 규격,
     * user_info/content_info/block_info/uservalues 구조).
     * fire-and-forget: 파싱/로그 기록 성공 여부와 무관하게 즉시 204 응답.
     */
    public function handle(Request $request): Response
    {
        try {
            $payload = json_decode(
                (string) $request->input('json_data'),
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );

            $summary = $this->callbackService->summarize($payload);

            Log::channel('kollus_lms')->info('lms_callback', $summary);
        } catch (Throwable $e) {
            // json_data 파싱/로그 기록 실패: 클라이언트 응답에는 영향 없음, 애플리케이션 로그로만 기록
            report($e);
        }

        return response()->noContent();
    }
}
