<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class KollusNextEpisodeCallbackRequest extends FormRequest
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
            'mck' => ['required', 'string'],
            'id'  => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * Kollus 서버가 호출하는 엔드포인트이므로 redirect 대신 422 JSON으로 반환.
     */
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}
