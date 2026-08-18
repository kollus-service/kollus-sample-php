<?php

namespace App\Libraries\Kollus;

use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use RuntimeException;

class KollusUploadService
{
    /** 업로드 URL 생성 API 엔드포인트 (고정값) */
    private const CREATE_URL_ENDPOINT = 'https://c-api-kr.kollus.com/api/upload/create-url';
    private const CREATE_URL_ENDPOINT_PASSTHROUGH = 'https://upload.kr.kollus.com/api/v1/create_url';

    /** 업로드 URL 기본 유효 시간 (초) */
    private const DEFAULT_EXPIRE_TIME = 600;

    /** 업로드 URL 최대 유효 시간 (초) */
    private const MAX_EXPIRE_TIME = 21600;

    public function __construct(
        private readonly CURLRequest $client,
        private readonly string $accessToken,
    ) {
    }

    /**
     * Kollus API를 호출하여 일회용 업로드 URL을 발급받는다.
     *
     * @param string      $title         콘텐츠 제목 (미지정 시 파일명 사용)
     * @param string|null $categoryKey   저장할 카테고리 키 (미지정 시 기본 카테고리)
     * @param int         $expireTime    URL 유효 시간 (초, 최대 21600)
     * @param int         $isAudioUpload 오디오 파일 여부 (0: 비디오, 1: 오디오)
     * @param int         $isPassthrough 패스스루 업로드 여부 (0: 일반, 1: 패스스루)
     * @param string|null $profileKey    인코딩 프로파일 키 (패스스루 시 필수)
     * @return array{upload_url: string, progress_url: string, upload_file_key: string, expired_at: int}
     *
     * @throws RuntimeException API 호출 실패 또는 Kollus 에러 응답 시
     */
    public function createUploadUrl(
        string $title,
        ?string $categoryKey = null,
        int $expireTime = self::DEFAULT_EXPIRE_TIME,
        int $isAudioUpload = 0,
        int $isPassthrough = 0,
        ?string $profileKey = null,
    ): array {
        $expireTime = min(max($expireTime, 1), self::MAX_EXPIRE_TIME);

        $body = array_filter([
            'expire_time'          => $expireTime,
            'title'                => $title,
            'category_key'         => $categoryKey,
            'is_encryption_upload' => 0,
            'is_audio_upload'      => $isAudioUpload,
            'is_passthrough'       => $isPassthrough,
            'profile_key'          => $profileKey,
        ], static fn ($v) => $v !== null && $v !== '');

        $options = [
            'query'       => ['access_token' => $this->accessToken],
            // http_errors를 끄고 상태 코드/본문을 직접 판단 — Laravel Http::failed()와 동일한 방식
            'http_errors' => false,
        ];

        try {
            // 패스스루 엔드포인트는 form-urlencoded, 일반 엔드포인트는 JSON
            $response = $isPassthrough === 1
                ? $this->client->post(self::CREATE_URL_ENDPOINT_PASSTHROUGH, $options + ['form_params' => $body])
                : $this->client->post(self::CREATE_URL_ENDPOINT, $options + ['json' => $body]);
        } catch (HTTPException $e) {
            throw new RuntimeException('Kollus API 호출 실패: ' . $e->getMessage(), previous: $e);
        }

        if ($response->getStatusCode() >= 400) {
            throw new RuntimeException(
                'Kollus API 호출 실패 (HTTP ' . $response->getStatusCode() . '): ' . $response->getBody()
            );
        }

        $json = json_decode((string) $response->getBody(), true) ?? [];

        // 패스스루/일반 응답 포맷을 공통 구조로 정규화하여 반환
        if ($isPassthrough === 1) {
            // 패스스루 응답: { error: 0, message: "", result: { ..., will_be_expired_at } }
            if (($json['error'] ?? -1) !== 0) {
                throw new RuntimeException('업로드 URL 생성 실패: ' . ($json['message'] ?? '알 수 없는 오류'));
            }

            $result = $json['result'];

            return [
                'upload_url'      => $result['upload_url'],
                'progress_url'    => $result['progress_url'],
                'upload_file_key' => $result['upload_file_key'],
                'expired_at'      => $result['will_be_expired_at'],
                'is_passthrough'  => $isPassthrough,
                'profile_key'     => $profileKey,
            ];
        }

        // 일반 응답: { status: "ok", data: { ..., expired_at } }
        if (($json['status'] ?? null) !== 'ok') {
            throw new RuntimeException('업로드 URL 생성 실패: ' . ($json['message'] ?? '알 수 없는 오류'));
        }

        return $json['data'];
    }
}
