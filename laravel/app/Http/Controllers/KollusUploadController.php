<?php
/**
 * GET /kollus/upload, POST /kollus/upload/create-url — 업로드 페이지 렌더링 및 Kollus 일회용 업로드 URL 발급(AJAX) 컨트롤러.
 */

namespace App\Http\Controllers;

use App\Services\KollusUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class KollusUploadController extends Controller
{
    /** 제목/파일명 입력 최대 길이 (일반적인 VARCHAR(255) 컬럼 길이 기준) */
    private const MAX_TEXT_LENGTH = 255;

    /** Kollus Upload API 연동(업로드 URL 발급) 서비스 */
    private readonly KollusUploadService $_uploadService;

    /**
     * @param KollusUploadService $uploadService Kollus Upload API 연동(업로드 URL 발급) 서비스
     */
    public function __construct(KollusUploadService $uploadService)
    {
        $this->_uploadService = $uploadService;
    }

    /**
     * 업로드 페이지를 렌더링한다.
     *
     * @return View 드래그앤드롭 업로드 폼 뷰 (kollus.upload)
     */
    public function index(): View
    {
        return view('kollus.upload');
    }

    /**
     * Kollus API를 호출하여 일회용 업로드 URL을 발급하고 JSON으로 반환한다.
     * access_token은 서버에서만 사용하며 프론트엔드에 노출되지 않음.
     *
     * 응답:
     *   { upload_url, progress_url, upload_file_key, expired_at }
     *
     * @param Request $request title / file_name / category_key / expire_time / is_audio / is_passthrough / profile_key
     * @return JsonResponse 발급된 업로드 URL 정보. 실패 시 { error } 와 함께 422(입력 오류) 또는 503(Kollus API 오류)
     */
    public function createUrl(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'          => ['sometimes', 'nullable', 'string', 'max:' . self::MAX_TEXT_LENGTH],
            'file_name'      => ['sometimes', 'nullable', 'string', 'max:' . self::MAX_TEXT_LENGTH],
            'category_key'   => ['sometimes', 'nullable', 'string'],
            'expire_time'    => [
                'sometimes',
                'integer',
                'min:' . KollusUploadService::MIN_EXPIRE_TIME,
                'max:' . KollusUploadService::MAX_EXPIRE_TIME,
            ],
            'is_audio'       => ['sometimes', 'boolean'],
            'is_passthrough' => ['sometimes', 'boolean'],
            'profile_key'    => ['sometimes', 'nullable', 'string'],
        ]);

        $isPassthrough = (int) ($validated['is_passthrough'] ?? 0);

        // 패스스루 모드에서 profile_key 필수 검증 (뷰에서도 체크하지만 서버에서도 보장)
        if ($isPassthrough === 1 && empty($validated['profile_key'])) {
            return response()->json(
                ['error' => '패스스루 업로드 시 인코딩 프로파일 키는 필수입니다.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        // 제목 미입력 시 파일명(확장자 포함)을 제목으로 사용. 패스스루 시 제목 불필요하므로 빈 문자열 허용
        $title = filled($validated['title'] ?? null)
            ? $validated['title']
            : ($validated['file_name'] ?? 'untitled');

        try {
            $data = $this->_uploadService->createUploadUrl(
                title: $title,
                categoryKey: $validated['category_key'] ?? null,
                expireTime: $validated['expire_time'] ?? KollusUploadService::DEFAULT_EXPIRE_TIME,
                isAudioUpload: $validated['is_audio'] ?? 0,
                isPassthrough: $isPassthrough,
                profileKey: $validated['profile_key'] ?? null,
            );
        } catch (RuntimeException $e) {
            // Kollus API 오류는 503으로 반환 — 클라이언트 문제가 아님을 구분
            return response()->json(['error' => $e->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json($data);
    }
}
