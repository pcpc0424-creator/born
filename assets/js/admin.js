/**
 * 본투어 인터내셔날 - 관리자 JavaScript
 */

// 전역 유틸리티
const BornAdmin = {
    // API 요청
    async api(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const config = { ...defaultOptions, ...options };

        if (config.body && typeof config.body === 'object' && !(config.body instanceof FormData)) {
            config.body = JSON.stringify(config.body);
        }

        if (config.body instanceof FormData) {
            delete config.headers['Content-Type'];
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || '요청 처리 중 오류가 발생했습니다.');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    // 토스트 알림
    toast(message, type = 'info') {
        const container = document.querySelector('.toast-container') || this.createToastContainer();

        const toast = document.createElement('div');
        toast.className = `toast toast-${type} toast-enter`;
        toast.innerHTML = `
            <svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                ${this.getToastIcon(type)}
            </svg>
            <span class="toast-message">${message}</span>
            <span class="toast-close" onclick="this.parentElement.remove()">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </span>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.remove('toast-enter');
            toast.classList.add('toast-exit');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    },

    createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
        return container;
    },

    getToastIcon(type) {
        const icons = {
            success: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
            error: '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
            warning: '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            info: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>'
        };
        return icons[type] || icons.info;
    },

    // 모달 열기
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.classList.add('no-scroll');
        }
    },

    // 모달 닫기
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.classList.remove('no-scroll');
        }
    },

    // 확인 모달
    async confirm(message, title = '확인') {
        return new Promise((resolve) => {
            const modalHtml = `
                <div class="modal-backdrop active" id="confirm-modal">
                    <div class="modal">
                        <div class="modal-header">
                            <h3 class="modal-title">${title}</h3>
                            <span class="modal-close" onclick="BornAdmin.closeModal('confirm-modal')">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 6L6 18M6 6l12 12"/>
                                </svg>
                            </span>
                        </div>
                        <div class="modal-body">
                            <p>${message}</p>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" id="confirm-cancel">취소</button>
                            <button class="btn btn-primary" id="confirm-ok">확인</button>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            document.body.classList.add('no-scroll');

            const modal = document.getElementById('confirm-modal');
            const cancelBtn = document.getElementById('confirm-cancel');
            const okBtn = document.getElementById('confirm-ok');

            const cleanup = (result) => {
                modal.remove();
                document.body.classList.remove('no-scroll');
                resolve(result);
            };

            cancelBtn.addEventListener('click', () => cleanup(false));
            okBtn.addEventListener('click', () => cleanup(true));
            modal.addEventListener('click', (e) => {
                if (e.target === modal) cleanup(false);
            });
        });
    },

    // 삭제 확인
    async confirmDelete(itemName = '이 항목') {
        return this.confirm(`${itemName}을(를) 삭제하시겠습니까?<br><small style="color: var(--gray-500);">삭제된 데이터는 복구할 수 없습니다.</small>`, '삭제 확인');
    },

    // 폼 데이터 수집
    getFormData(formId) {
        const form = document.getElementById(formId);
        if (!form) return null;

        const formData = new FormData(form);
        const data = {};

        formData.forEach((value, key) => {
            if (data[key]) {
                if (!Array.isArray(data[key])) {
                    data[key] = [data[key]];
                }
                data[key].push(value);
            } else {
                data[key] = value;
            }
        });

        return data;
    },

    // 폼 유효성 검사
    validateForm(formId, rules) {
        const form = document.getElementById(formId);
        const errors = {};

        for (const [field, rule] of Object.entries(rules)) {
            const input = form.querySelector(`[name="${field}"]`);
            if (!input) continue;

            const value = input.value.trim();

            if (rule.required && !value) {
                errors[field] = rule.message || `${rule.label}을(를) 입력해주세요.`;
                this.setFieldError(input, errors[field]);
            } else if (rule.minLength && value.length < rule.minLength) {
                errors[field] = `${rule.label}은(는) ${rule.minLength}자 이상이어야 합니다.`;
                this.setFieldError(input, errors[field]);
            } else if (rule.pattern && !rule.pattern.test(value)) {
                errors[field] = rule.patternMessage || `${rule.label} 형식이 올바르지 않습니다.`;
                this.setFieldError(input, errors[field]);
            } else {
                this.clearFieldError(input);
            }
        }

        return Object.keys(errors).length === 0 ? null : errors;
    },

    setFieldError(input, message) {
        input.classList.add('error');
        let errorEl = input.parentElement.querySelector('.form-error');
        if (!errorEl) {
            errorEl = document.createElement('span');
            errorEl.className = 'form-error';
            input.parentElement.appendChild(errorEl);
        }
        errorEl.textContent = message;
    },

    clearFieldError(input) {
        input.classList.remove('error');
        const errorEl = input.parentElement.querySelector('.form-error');
        if (errorEl) errorEl.remove();
    },

    // 로딩 상태
    showLoading(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (element) {
            element.classList.add('loading');
            if (element.tagName === 'BUTTON') {
                element.disabled = true;
                element.dataset.originalText = element.innerHTML;
                element.innerHTML = '<span class="loading-spinner" style="width:20px;height:20px;border-width:2px;"></span>';
            }
        }
    },

    hideLoading(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (element) {
            element.classList.remove('loading');
            if (element.tagName === 'BUTTON') {
                element.disabled = false;
                if (element.dataset.originalText) {
                    element.innerHTML = element.dataset.originalText;
                }
            }
        }
    },

    // 파일 업로드 미리보기
    previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        if (!preview || !input.files || !input.files[0]) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    },

    // 날짜 포맷팅
    formatDate(date, format = 'YYYY-MM-DD') {
        if (!date) return '';
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');

        return format
            .replace('YYYY', year)
            .replace('MM', month)
            .replace('DD', day);
    },

    // 숫자 포맷팅
    formatNumber(num) {
        return new Intl.NumberFormat('ko-KR').format(num);
    },

    // 디바운스
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // 사이드바 토글
    toggleSidebar() {
        const sidebar = document.querySelector('.admin-sidebar');
        if (sidebar) {
            sidebar.classList.toggle('active');
        }
    },

    // 테이블 선택
    initTableSelect(tableId, callback) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const checkAll = table.querySelector('.check-all');
        const checkboxes = table.querySelectorAll('.check-item');

        if (checkAll) {
            checkAll.addEventListener('change', () => {
                checkboxes.forEach(cb => cb.checked = checkAll.checked);
                if (callback) callback(this.getSelectedIds(tableId));
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                checkAll.checked = [...checkboxes].every(c => c.checked);
                if (callback) callback(this.getSelectedIds(tableId));
            });
        });
    },

    getSelectedIds(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return [];

        return [...table.querySelectorAll('.check-item:checked')]
            .map(cb => cb.value);
    },

    // 엑셀 다운로드
    async downloadExcel(url, filename) {
        try {
            const response = await fetch(url);
            const blob = await response.blob();
            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(downloadUrl);
        } catch (error) {
            this.toast('다운로드에 실패했습니다.', 'error');
        }
    }
};

// DOM 로드 완료 시 초기화
document.addEventListener('DOMContentLoaded', () => {
    // 모달 외부 클릭 시 닫기
    document.querySelectorAll('.modal-backdrop').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
                document.body.classList.remove('no-scroll');
            }
        });
    });

    // ESC 키로 모달 닫기
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal-backdrop.active');
            if (activeModal) {
                activeModal.classList.remove('active');
                document.body.classList.remove('no-scroll');
            }
        }
    });

    // 토글 스위치 초기화
    document.querySelectorAll('.toggle-switch input').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const event = new CustomEvent('toggle-change', {
                detail: {
                    name: this.name,
                    checked: this.checked
                }
            });
            this.dispatchEvent(event);
        });
    });

    // 검색 입력 디바운스
    document.querySelectorAll('.search-input').forEach(input => {
        input.addEventListener('input', BornAdmin.debounce((e) => {
            const event = new CustomEvent('search', {
                detail: { query: e.target.value }
            });
            input.dispatchEvent(event);
        }, 300));
    });
});

// 전역으로 노출
window.BornAdmin = BornAdmin;
