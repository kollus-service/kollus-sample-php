<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kollus 콘텐츠 업로드</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 40px 16px;
        }

        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
            padding: 32px;
            width: 100%;
            max-width: 560px;
        }

        h1 { font-size: 1.25rem; font-weight: 600; margin-bottom: 24px; color: #111; }

        .field { margin-bottom: 16px; }
        label { display: block; font-size: .85rem; font-weight: 500; color: #444; margin-bottom: 6px; }
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: .95rem;
            outline: none;
            transition: border-color .15s;
        }
        input:focus { border-color: #4f46e5; }

        .file-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, background .2s;
        }
        .file-area:hover, .file-area.drag-over { border-color: #4f46e5; background: #f5f3ff; }
        .file-area input { display: none; }
        .file-area .icon { font-size: 2rem; margin-bottom: 8px; }
        .file-area .hint { font-size: .85rem; color: #888; margin-top: 4px; }
        .file-name { font-size: .9rem; color: #4f46e5; font-weight: 500; margin-top: 8px; word-break: break-all; }

        .btn {
            display: block;
            width: 100%;
            padding: 11px;
            background: #4f46e5;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: 24px;
            transition: background .15s;
        }
        .btn:hover:not(:disabled) { background: #4338ca; }
        .btn:disabled { background: #a5b4fc; cursor: not-allowed; }

        /* 진행률 영역 */
        #progress-section { display: none; margin-top: 24px; }
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: .85rem;
            color: #555;
            margin-bottom: 6px;
        }
        .progress-bar-track {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 99px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background: #4f46e5;
            border-radius: 99px;
            width: 0%;
            transition: width .3s ease;
        }

        /* 상태 메시지 */
        #status-msg {
            margin-top: 16px;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: .9rem;
            display: none;
        }
        #status-msg.success { background: #ecfdf5; color: #065f46; border: 1px solid #6ee7b7; }
        #status-msg.error   { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
        #status-msg.info    { background: #eff6ff; color: #1e40af; border: 1px solid #93c5fd; }
    </style>
</head>
<body>
<div class="card">
    <h1>콘텐츠 업로드</h1>

    <form id="upload-form">
        {{-- 패스스루 체크박스 --}}
        <div class="field" style="display:flex;align-items:center;gap:10px;">
            <input type="checkbox" id="is-passthrough" style="width:16px;height:16px;cursor:pointer;accent-color:#4f46e5;">
            <label for="is-passthrough" style="margin:0;cursor:pointer;">패스스루 업로드</label>
        </div>

        {{-- 제목 (패스스루 시 숨김) --}}
        <div class="field" id="field-title">
            <label for="title">제목 <span style="color:#aaa;font-weight:400">(선택 — 미입력 시 파일명 사용)</span></label>
            <input type="text" id="title" placeholder="미입력 시 파일명으로 저장됩니다">
        </div>

        {{-- 인코딩 프로파일 키 (패스스루 시만 노출) --}}
        <div class="field" id="field-profile-key" style="display:none;">
            <label for="profile-key">인코딩 프로파일 키 <span style="color:#e11d48">*</span></label>
            <input type="text" id="profile-key" placeholder="패스스루 업로드에 사용할 프로파일 키를 입력하세요">
        </div>

        <div class="field">
            <label for="category-key">카테고리 키 <span style="color:#aaa;font-weight:400">(선택)</span></label>
            <input type="text" id="category-key" placeholder="미입력 시 기본 카테고리에 저장됩니다">
        </div>

        <div class="field">
            <label for="expire-time">업로드 URL 유효 시간 (초)</label>
            <input type="number" id="expire-time" value="600" min="60" max="21600">
        </div>

        <div class="field">
            <label>파일 <span style="color:#e11d48">*</span></label>
            <div class="file-area" id="file-area">
                <input type="file" id="file-input" accept="video/*,audio/*">
                <div class="icon">📁</div>
                <div>클릭하거나 파일을 여기에 드래그하세요</div>
                <div class="hint">video/*, audio/* 형식 지원</div>
                <div class="file-name" id="file-name" style="display:none"></div>
            </div>
        </div>

        <button type="submit" class="btn" id="submit-btn">업로드 시작</button>
    </form>

    <div id="progress-section">
        <div class="progress-label">
            <span id="progress-status">업로드 중...</span>
            <span id="progress-pct">0%</span>
        </div>
        <div class="progress-bar-track">
            <div class="progress-bar-fill" id="progress-bar"></div>
        </div>
    </div>

    <div id="status-msg"></div>
</div>

<script>
(function () {
    'use strict';

    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
    const CREATE_URL_ENDPOINT = '{{ route("kollus.upload.create-url") }}';

    /* --- 패스스루 토글 --- */
    const passthroughCheckbox = document.getElementById('is-passthrough');
    const fieldTitle           = document.getElementById('field-title');
    const fieldProfileKey      = document.getElementById('field-profile-key');

    passthroughCheckbox.addEventListener('change', () => {
        const checked = passthroughCheckbox.checked;
        fieldTitle.style.display      = checked ? 'none' : '';
        fieldProfileKey.style.display = checked ? '' : 'none';
        if (!checked) {
            document.getElementById('profile-key').value = '';
        }
    });

    const form        = document.getElementById('upload-form');
    const fileArea    = document.getElementById('file-area');
    const fileInput   = document.getElementById('file-input');
    const fileNameEl  = document.getElementById('file-name');
    const submitBtn   = document.getElementById('submit-btn');
    const progressSec = document.getElementById('progress-section');
    const progressBar = document.getElementById('progress-bar');
    const progressPct = document.getElementById('progress-pct');
    const progressSts = document.getElementById('progress-status');
    const statusMsg   = document.getElementById('status-msg');

    /* --- 파일 선택 UI --- */
    fileArea.addEventListener('click', () => fileInput.click());

    fileArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        fileArea.classList.add('drag-over');
    });
    fileArea.addEventListener('dragleave', () => fileArea.classList.remove('drag-over'));
    fileArea.addEventListener('drop', (e) => {
        e.preventDefault();
        fileArea.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file) setFile(file);
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files[0]) setFile(fileInput.files[0]);
    });

    function setFile(file) {
        // DataTransfer로 input에 파일 설정 (drag-and-drop 시 input 동기화)
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;

        fileNameEl.textContent = `${file.name} (${formatBytes(file.size)})`;
        fileNameEl.style.display = 'block';
    }

    function formatBytes(bytes) {
        if (bytes < 1024)        return bytes + ' B';
        if (bytes < 1024 ** 2)   return (bytes / 1024).toFixed(1) + ' KB';
        if (bytes < 1024 ** 3)   return (bytes / 1024 ** 2).toFixed(1) + ' MB';
        return (bytes / 1024 ** 3).toFixed(2) + ' GB';
    }

    /* --- 상태 메시지 --- */
    function showStatus(msg, type) {
        statusMsg.textContent = msg;
        statusMsg.className   = type;
        statusMsg.style.display = 'block';
    }

    /* --- 진행률 업데이트 --- */
    function setProgress(pct, statusText) {
        const clamped = Math.min(Math.max(pct, 0), 100);
        progressBar.style.width = clamped + '%';
        progressPct.textContent = clamped + '%';
        if (statusText) progressSts.textContent = statusText;
    }

    /* --- 폼 제출 --- */
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const isPassthrough = passthroughCheckbox.checked;
        const title         = document.getElementById('title').value.trim();
        const profileKey    = document.getElementById('profile-key').value.trim();
        const categoryKey   = document.getElementById('category-key').value.trim();
        const expireTime    = parseInt(document.getElementById('expire-time').value, 10);
        const file          = fileInput.files[0];

        if (!file) return showStatus('파일을 선택해 주세요.', 'error');
        if (isPassthrough && !profileKey) return showStatus('인코딩 프로파일 키를 입력해 주세요.', 'error');

        // UI 초기화
        statusMsg.style.display  = 'none';
        progressSec.style.display = 'block';
        setProgress(0, '업로드 URL 발급 중...');
        submitBtn.disabled = true;

        /* 1단계: 서버에서 업로드 URL 발급 */
        let uploadData;
        try {
            const res = await fetch(CREATE_URL_ENDPOINT, {
                method:  'POST',
                headers: {
                    'Content-Type':  'application/json',
                    'X-CSRF-TOKEN':  CSRF_TOKEN,
                    'Accept':        'application/json',
                },
                body: JSON.stringify({
                    title:          isPassthrough ? null : (title || null),
                    file_name:      file.name,
                    category_key:   categoryKey || null,
                    expire_time:    expireTime,
                    is_passthrough: isPassthrough ? 1 : 0,
                    profile_key:    isPassthrough ? profileKey : null,
                }),
            });

            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.error || `서버 오류 (HTTP ${res.status})`);
            }

            uploadData = await res.json();
        } catch (err) {
            showStatus('업로드 URL 발급 실패: ' + err.message, 'error');
            submitBtn.disabled = false;
            return;
        }

        /* 2단계: Kollus 업로드 서버로 파일 직접 전송 */
        setProgress(0, '파일 전송 중...');

        const formData = new FormData();
        formData.append('upload-file',       file);           // Kollus 규격: 파일 필드명 고정
        formData.append('accept',            'application/json');
        formData.append('disable_alert',     '1');
        formData.append('redirection_scope', 'no');

        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', (e) => {
            if (!e.lengthComputable) return;
            const pct = Math.round((e.loaded / e.total) * 100);
            setProgress(pct, `파일 전송 중... (${formatBytes(e.loaded)} / ${formatBytes(e.total)})`);
        });

        xhr.addEventListener('load', () => {
            submitBtn.disabled = false;
            setProgress(100, '완료');

            // 응답이 JSON인 경우 결과 확인
            try {
                const result = JSON.parse(xhr.responseText);
                if (result.result === 'S' || xhr.status === 200) {
                    showStatus('업로드가 완료되었습니다. 파일 키: ' + uploadData.upload_file_key, 'success');
                } else {
                    showStatus('업로드 실패: ' + (result.message || '알 수 없는 오류'), 'error');
                }
            } catch {
                // JSON 파싱 실패 시 HTTP 상태로 판단
                if (xhr.status === 200) {
                    showStatus('업로드가 완료되었습니다. 파일 키: ' + uploadData.upload_file_key, 'success');
                } else {
                    showStatus('업로드 중 오류가 발생했습니다 (HTTP ' + xhr.status + ')', 'error');
                }
            }
        });

        xhr.addEventListener('error', () => {
            submitBtn.disabled = false;
            // CORS 오류 시 직접 업로드가 차단될 수 있음 — 서버 프록시 방식 고려 필요
            showStatus('네트워크 오류 또는 CORS 정책으로 업로드에 실패했습니다.', 'error');
        });

        xhr.addEventListener('abort', () => {
            submitBtn.disabled = false;
            showStatus('업로드가 취소되었습니다.', 'info');
        });

        xhr.open('POST', uploadData.upload_url);
        xhr.send(formData);
    });
}());
</script>
</body>
</html>
