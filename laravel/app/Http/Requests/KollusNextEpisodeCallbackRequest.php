<?php
/**
 * 다음 회차 콜백 요청 검증(mck 필수, id 선택). 실패 시 redirect 대신 422 JSON을 반환한다.
 */

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class KollusNextEpisodeCallbackRequest extends FormRequest
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
     * 다음 회차 콜백 파라미터 검증 규칙 (mck: 현재 재생 중인 MCK, id: client_user_id).
     *
     * @return array<string, array<int, string>> 필드별 검증 규칙
     */
    public function rules(): array
    {
        return [
            'mck' => ['required', 'string'],
            'id'  => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * Kollus 서버가 호출하는 엔드포인트이므로 redirect 대신 422 JSON으로 반환.
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
