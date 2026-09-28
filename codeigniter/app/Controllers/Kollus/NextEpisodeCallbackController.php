<?php
/**
 * Kollus 다음 회차 연속 재생 콜백(next-episode-callback) 컨트롤러 — 다음 회차 SI 링크를 JSON으로 응답한다.
 */

namespace App\Controllers\Kollus;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class NextEpisodeCallbackController extends BaseController
{
    /**
     * Kollus 플레이어의 다음 회차 연속 재생 콜백을 처리한다.
     *
     * 응답 규격 (Kollus 문서 기준):
     *   - plain JSON { result, next_url }
     *   - next_url은 반드시 SI 링크(/si) 형태여야 함
     *
     * @return ResponseInterface 다음 회차가 있으면 { result: 1, next_url }, 없거나 생성 실패 시 { result: 0 }, 검증 실패 422
     */
    public function handle()
    {
        if (! $this->validate([
            'mck' => 'required|string',
            'id'  => 'permit_empty|string',
        ])) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_UNPROCESSABLE_ENTITY)
                ->setJSON(['errors' => $this->validator->getErrors()]);
        }

        try {
            $nextUrl = service('kollusNextEpisodeCallbackService')->buildNextUrl(
                (string) $this->request->getVar('mck'),
                (string) ($this->request->getVar('id') ?? 'unknown'),
            );
        } catch (RuntimeException $e) {
            // 토큰 생성 실패: 결과 0으로 응답하여 플레이어가 연속 재생을 중단하도록 처리
            log_message('error', 'kollus.next-episode-callback: ' . $e->getMessage());

            return $this->response->setJSON(['result' => 0]);
        }

        if ($nextUrl === null) {
            return $this->response->setJSON(['result' => 0]);
        }

        return $this->response->setJSON(['result' => 1, 'next_url' => $nextUrl]);
    }
}
