<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class KollusDrmCallbackRequest extends FormRequest
{
    /**
     * Kollus 서버에서 호출하는 내부 콜백이므로 별도 인증 없이 허용.
     * 실제 운영 시 IP 화이트리스트 미들웨어를 추가하는 것을 권장.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * items 파라미터는 JSON 문자열로 전송되므로 검증 전에 배열로 파싱.
     * 파싱 실패 시 rules()의 'array' 검사에서 걸림.
     */
    protected function prepareForValidation(): void
    {
        $raw = $this->input('items');

        if (is_string($raw)) {
            $decoded = json_decode($raw, associative: true);
            // json_decode 실패 시 null 반환 → 'array' 규칙에서 걸림
            $this->merge(['items' => $decoded ?? $raw]);
        }
    }

    public function rules(): array
    {
        return [
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.kind'              => ['required', 'integer', 'in:1,2,3'],
            'items.*.client_user_id'    => ['required', 'string'],
            'items.*.player_id'         => ['required', 'string'],
            'items.*.media_content_key' => ['required', 'string'],
            // kind3 전용 필수 필드
            'items.*.session_key'       => ['sometimes', 'nullable', 'string'],
            'items.*.start_at'          => ['sometimes', 'nullable', 'integer'],
            // 공통 선택 필드
            'items.*.hardware_id'       => ['sometimes', 'nullable', 'string'],
            'items.*.device_name'       => ['sometimes', 'nullable', 'string'],
            'items.*.localtime'         => ['sometimes', 'nullable', 'integer'],
            'items.*.uservalues'        => ['sometimes', 'nullable'],
            // kind3 추가 선택 필드
            'items.*.content_expired'   => ['sometimes', 'nullable', 'integer', 'in:0,1'],
            'items.*.check_expired'     => ['sometimes', 'nullable', 'integer', 'in:0,1'],
            'items.*.reset_req'         => ['sometimes', 'nullable', 'integer', 'in:0,1'],
            'items.*.expiration_date'   => ['sometimes', 'nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'       => 'items 파라미터가 필요합니다.',
            'items.array'          => 'items는 유효한 JSON 배열이어야 합니다.',
            'items.*.kind.in'      => 'kind 값은 1(다운로드 승인), 2(다운로드 완료), 3(오프라인 재생)이어야 합니다.',
        ];
    }

    /**
     * Kollus 서버가 호출하는 엔드포인트이므로 redirect 대신 422 JSON 반환.
     */
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }
}
