<?php
/**
 * Kollus LMS 콜백 컨트롤러 — 시청 이력/학습 진도 페이로드를 수신해 요약 후 전용 로그에 기록한다.
 */

namespace App\Controllers\Kollus;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class LmsCallbackController extends BaseController
{
    /**
     * json_data 디코딩 시 허용할 최대 중첩 깊이.
     * PHP json_decode 기본값과 동일 — Kollus LMS 페이로드(user_info/content_info/block_info)는 3~4단계라 충분하다.
     */
    private const JSON_MAX_DEPTH = 512;

    /**
     * Kollus LMS 콜백을 수신하여 시청 이력/학습 진도 데이터를 로그로 기록한다.
     *
     * 요청 바디는 POST 파라미터 'json_data'에 JSON 문자열로 전달됨 (Kollus 규격,
     * user_info/content_info/block_info/uservalues 구조).
     * fire-and-forget: 파싱/로그 기록 성공 여부와 무관하게 즉시 204 응답.
     *
     * @return ResponseInterface 항상 204 No Content
     */
    public function handle()
    {
        try {
            $payload = json_decode(
                (string) $this->request->getVar('json_data'),
                true,
                self::JSON_MAX_DEPTH,
                JSON_THROW_ON_ERROR,
            );

            $summary = service('kollusLmsCallbackService')->summarize($payload);

            service('kollusLmsLogger')->info('lms_callback', $summary);
        } catch (Throwable $e) {
            // json_data 파싱/로그 기록 실패: 클라이언트 응답에는 영향 없음, 애플리케이션 로그로만 기록
            log_message('error', 'kollus.lms-callback: ' . $e->getMessage());
        }

        return $this->response->setStatusCode(ResponseInterface::HTTP_NO_CONTENT);
    }
}
