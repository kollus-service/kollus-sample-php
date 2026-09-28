<?php
/**
 * Kollus 재생 콜백(play-callback) 컨트롤러 — kind1(만료 설정)/kind3(최종 승인) 요청을 검증하고 JWT 응답을 반환한다.
 */

namespace App\Controllers\Kollus;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Kollus as KollusConfig;
use RuntimeException;

class PlayCallbackController extends BaseController
{
    /**
     * 재생 차단 시 플레이어에 노출할 기본 메시지.
     * 서비스 정책에 맞게 변경하거나 DB/config에서 가져오도록 수정 가능.
     */
    private const DENY_MESSAGE =
        "해당 ID는 강의 공유, 양도, 불법거래 등 \n부정사용 행위 사유로 수강정지 조치되었습니다.";

    /**
     * Kollus 플레이어로부터 콜백을 수신하고 JWT 응답을 반환한다.
     *
     * 응답 규격 (Kollus 문서 기준):
     *   - Content-Type : text/plain; charset=utf-8
     *   - X-KOLLUS-USERKEY : {CUSTOM_KEY}  (반드시 포함)
     *   - Body : JWT 인코딩된 JSON 응답
     *
     * @return ResponseInterface 성공 시 200 + JWT 본문, 검증 실패 422, JWT 생성 실패 500
     */
    public function handle()
    {
        if (! $this->validate([
            'kind'              => 'required|integer|in_list[1,3]',
            'client_user_id'    => 'required|string',
            'player_id'         => 'required|string',
            'media_content_key' => 'required|string',
            'hardware_id'       => 'permit_empty|string',
            'device_name'       => 'permit_empty|string',
            'localtime'         => 'permit_empty|integer',
            'uservalues'        => 'permit_empty|string',
        ], [
            'kind' => ['in_list' => 'kind 값은 1(재생 만료 설정) 또는 3(최종 재생 승인)이어야 합니다.'],
        ])) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)
                ->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $kind = (int) $this->request->getVar('kind');

        // TODO: 실제 서비스에서는 client_user_id / media_content_key로 DB 조회 후 결정
        $isAllowed = true;

        try {
            $jwt = service('kollusPlayCallbackService')->buildResponse(
                $kind,
                $isAllowed,
                $isAllowed ? null : self::DENY_MESSAGE,
            );
        } catch (RuntimeException $e) {
            // JWT 생성 실패는 서버 설정 오류이므로 500 반환
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR)
                ->setBody('Kollus 콜백 응답 생성에 실패했습니다: ' . $e->getMessage());
        }

        return $this->response
            ->setStatusCode(ResponseInterface::HTTP_OK)
            ->setContentType('text/plain', 'utf-8')
            ->setHeader('X-KOLLUS-USERKEY', config(KollusConfig::class)->customKey)
            ->setBody($jwt);
    }
}
