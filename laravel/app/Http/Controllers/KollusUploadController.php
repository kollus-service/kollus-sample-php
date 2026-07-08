<?php

namespace App\Http\Controllers;

use App\Services\KollusUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class KollusUploadController extends Controller
{
    public function __construct(
        private readonly KollusUploadService $uploadService,
    ) {}

    /**
     * 업로드 페이지를 렌더링한다.
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
     */
    public function createUrl(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'          => ['sometimes', 'nullable', 'string', 'max:255'],
            'file_name'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'category_key'   => ['sometimes', 'nullable', 'string'],
            'expire_time'    => ['sometimes', 'integer', 'min:60', 'max:21600'],
            'is_audio'       => ['sometimes', 'boolean'],
            'is_passthrough' => ['sometimes', 'boolean'],
            'profile_key'    => ['sometimes', 'nullable', 'string'],
        ]);

        $isPassthrough = (int) ($validated['is_passthrough'] ?? 0);

        // 패스스루 모드에서 profile_key 필수 검증 (뷰에서도 체크하지만 서버에서도 보장)
        if ($isPassthrough === 1 && empty($validated['profile_key'])) {
            return response()->json(['error' => '패스스루 업로드 시 인코딩 프로파일 키는 필수입니다.'], 422);
        }

        // 제목 미입력 시 파일명(확장자 포함)을 제목으로 사용. 패스스루 시 제목 불필요하므로 빈 문자열 허용
        $title = filled($validated['title'] ?? null)
            ? $validated['title']
            : ($validated['file_name'] ?? 'untitled');

        try {
            $data = $this->uploadService->createUploadUrl(
                title: $title,
                categoryKey: $validated['category_key'] ?? null,
                expireTime: $validated['expire_time'] ?? 600,
                isAudioUpload: $validated['is_audio'] ?? 0,
                isPassthrough: $isPassthrough,
                profileKey: $validated['profile_key'] ?? null,
            );
        } catch (RuntimeException $e) {
            // Kollus API 오류는 503으로 반환 — 클라이언트 문제가 아님을 구분
            return response()->json(['error' => $e->getMessage()], 503);
        }

        return response()->json($data);
    }
}
