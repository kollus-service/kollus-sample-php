<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class KollusPlayCallbackRequest extends FormRequest
{
    /**
     * Kollus 서버에서 호출하는 내부 콜백이므로 별도 인증 없이 허용.
     * 실제 운영 시 IP 화이트리스트 미들웨어를 추가하는 것을 권장.
     */
    public function authorize(): bool
    {
        return true;
    }

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

    public function messages(): array
    {
        return [
            'kind.in' => 'kind 값은 1(재생 만료 설정) 또는 3(최종 재생 승인)이어야 합니다.',
        ];
    }

    /**
     * Kollus 서버가 호출하는 엔드포인트이므로 redirect 대신 422 JSON으로 반환.
     * 기본 FormRequest는 웹 요청 시 redirect back하는데, 이 엔드포인트는 서버 간 통신이므로 부적절.
     */
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}
