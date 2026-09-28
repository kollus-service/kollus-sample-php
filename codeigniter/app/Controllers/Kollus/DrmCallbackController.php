<?php
/**
 * Kollus DRM 다운로드 콜백(drm-callback) 컨트롤러 — items 배열을 검증하고 항목별 승인 결과를 JWT로 응답한다.
 */

namespace App\Controllers\Kollus;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Kollus as KollusConfig;
use RuntimeException;

class DrmCallbackController extends BaseController
{
    /**
     * Kollus 플레이어로부터 DRM 다운로드 콜백을 수신하고 JWT 응답을 반환한다.
     *
     * 응답 규격 (Kollus 문서 기준):
     *   - Content-Type : text/plain; charset=utf-8
     *   - X-KOLLUS-USERKEY : {CUSTOM_KEY}  (반드시 포함)
     *   - Body : JWT 인코딩된 JSON 응답 ({ "data": [...] })
     *
     * @return ResponseInterface 성공 시 200 + JWT 본문, 검증 실패 422, JWT 생성 실패 500
     */
    public function handle()
    {
        // items 파라미터는 JSON 문자열로 전송되므로 검증 전에 배열로 파싱.
        // json_decode 실패 시 null 반환 → 아래 배열 검사에서 걸림
        $raw = $this->request->getVar('items');
        $items = is_string($raw) ? json_decode($raw, true) : $raw;

        if (! is_array($items) || $items === []) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)
                ->setJSON([
                    'errors' => ['items' => 'items는 최소 1개 이상의 항목을 가진 유효한 JSON 배열이어야 합니다.'],
                ]);
        }

        if (! $this->validateData(['items' => $items], [
            'items.*.kind'              => 'required|in_list[1,2,3]',
            'items.*.client_user_id'    => 'required|string',
            'items.*.player_id'         => 'required|string',
            'items.*.media_content_key' => 'required|string',
        ], [
            'items.*.kind' => [
                'in_list' => 'kind 값은 1(다운로드 승인), 2(다운로드 완료), 3(오프라인 재생)이어야 합니다.',
            ],
        ])) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)
                ->setJSON(['errors' => $this->validator->getErrors()]);
        }

        try {
            $jwt = service('kollusDrmCallbackService')->buildResponse($items);
        } catch (RuntimeException $e) {
            // JWT 인코딩 실패는 서버 설정 오류이므로 500 반환
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                ->setBody('Kollus DRM 콜백 응답 생성에 실패했습니다: ' . $e->getMessage());
        }

        return $this->response
            ->setStatusCode(ResponseInterface::HTTP_OK)
            ->setContentType('text/plain', 'utf-8')
            ->setHeader('X-KOLLUS-USERKEY', config(KollusConfig::class)->customKey)
            ->setBody($jwt);
    }
}
