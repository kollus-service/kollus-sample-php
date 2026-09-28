<?php
/**
 * 재생 콜백 요청 검증(kind/client_user_id/player_id/media_content_key 필수). 실패 시 redirect 대신 422 JSON을 반환한다.
 */

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class KollusPlayCallbackRequest extends FormRequest
{
    /**
     * Kollus 서버에서 호출하는 내부 콜백이므로 별도 인증 없이 허용.
     * 실제 운영 시 IP 화이트리스트 미들웨어를 추가하는 것을 권장.
     *
     * @return bool 항상 true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 재생 콜백 파라미터 검증 규칙. 필수/선택 구분은 Kollus 재생 콜백 규격을 따른다.
     *
     * @return array<string, array<int, string>> 필드별 검증 규칙
     */
    public function rules(): array
    {
        return [
            // Kollus 문서 기준 필수 파라미터
            'kind'              => ['required', 'integer', 'in:1,3'],
            'client_user_id'    => ['required', 'string'],
            'player_id'         => ['required', 'string'],
            'media_content_key' => ['required', 'string'],

            // 선택 파라미터
            'hardware_id'       => ['sometimes', 'nullable', 'string'],
            'device_name'       => ['sometimes', 'nullable', 'string'],
            'localtime'         => ['sometimes', 'nullable', 'integer'],
            'uservalues'        => ['sometimes', 'nullable', 'string'], // JSON string
        ];
    }

    /**
     * 검증 실패 시 응답에 사용할 한국어 메시지.
     *
     * @return array<string, string> "필드.규칙" 키 → 메시지
     */
    public function messages(): array
    {
        return [
            'kind.in' => 'kind 값은 1(재생 만료 설정) 또는 3(최종 재생 승인)이어야 합니다.',
        ];
    }

    /**
     * Kollus 서버가 호출하는 엔드포인트이므로 redirect 대신 422 JSON으로 반환.
     * 기본 FormRequest는 웹 요청 시 redirect back하는데, 이 엔드포인트는 서버 간 통신이므로 부적절.
     *
     * @param Validator $validator 실패한 검증기 (errors()로 필드별 메시지 조회)
     * @return never
     *
     * @throws HttpResponseException 422 JSON 응답을 담아 항상 throw
     */
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
