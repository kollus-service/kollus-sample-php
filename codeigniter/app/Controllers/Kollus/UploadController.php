<?php

namespace App\Controllers\Kollus;

use App\Controllers\BaseController;
use RuntimeException;

class UploadController extends BaseController
{
    /**
     * 업로드 페이지를 렌더링한다.
     */
    public function index()
    {
        return view('kollus/upload');
    }

    /**
     * Kollus API를 호출하여 일회용 업로드 URL을 발급하고 JSON으로 반환한다.
     * access_token은 서버에서만 사용하며 프론트엔드에 노출되지 않음.
     *
     * 응답:
     *   { upload_url, progress_url, upload_file_key, expired_at }
     */
    public function createUrl()
    {
        $data = $this->request->getJSON(true) ?? [];

        if (! $this->validateData($data, [
            'title'          => 'permit_empty|string|max_length[255]',
            'file_name'      => 'permit_empty|string|max_length[255]',
            'category_key'   => 'permit_empty|string',
            'expire_time'    => 'permit_empty|integer|greater_than_equal_to[60]|less_than_equal_to[21600]',
            'is_audio'       => 'permit_empty|in_list[0,1]',
            'is_passthrough' => 'permit_empty|in_list[0,1]',
            'profile_key'    => 'permit_empty|string',
        ])) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $isPassthrough = (int) ($data['is_passthrough'] ?? 0);

        // 패스스루 모드에서 profile_key 필수 검증 (뷰에서도 체크하지만 서버에서도 보장)
        if ($isPassthrough === 1 && empty($data['profile_key'])) {
            return $this->response->setStatusCode(422)
                ->setJSON(['error' => '패스스루 업로드 시 인코딩 프로파일 키는 필수입니다.']);
        }

        // 제목 미입력 시 파일명(확장자 포함)을 제목으로 사용. 패스스루 시 제목 불필요하므로 빈 문자열 허용
        $title = ! empty($data['title'])
            ? $data['title']
            : ($data['file_name'] ?? 'untitled');

        try {
            $result = service('kollusUploadService')->createUploadUrl(
                $title,
                $data['category_key'] ?? null,
                (int) ($data['expire_time'] ?? 600),
                (int) ($data['is_audio'] ?? 0),
                $isPassthrough,
                $data['profile_key'] ?? null,
            );
        } catch (RuntimeException $e) {
            // Kollus API 오류는 503으로 반환 — 클라이언트 문제가 아님을 구분
            return $this->response->setStatusCode(503)->setJSON(['error' => $e->getMessage()]);
        }

        return $this->response->setJSON($result);
    }
}
