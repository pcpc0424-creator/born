/**
 * 본투어 인터내셔날 - 여행자 JavaScript
 */

// 전역 유틸리티
const BornUser = {
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

            if (response.status === 401) {
                window.location.href = '/user/index.php?expired=1';
                return;
            }

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
        toast.className = `toast toast-${type} toast-enter-mobile`;
        toast.innerHTML = `
            <svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                ${this.getToastIcon(type)}
            </svg>
            <span class="toast-message">${message}</span>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
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

    // 로딩 오버레이
    showLoading(message = '잠시만 기다려주세요...') {
        let overlay = document.querySelector('.loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class="loading-spinner"></div>
                <p class="loading-text">${message}</p>
            `;
            document.body.appendChild(overlay);
        }
        overlay.style.display = 'flex';
        document.body.classList.add('no-scroll');
    },

    hideLoading() {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) {
            overlay.style.display = 'none';
            document.body.classList.remove('no-scroll');
        }
    },

    // 사이드바 토글
    openSidebar() {
        const overlay = document.querySelector('.sidebar-overlay');
        const sidebar = document.querySelector('.sidebar-menu');

        if (overlay && sidebar) {
            overlay.classList.add('active');
            sidebar.classList.add('active');
            document.body.classList.add('no-scroll');
        }
    },

    closeSidebar() {
        const overlay = document.querySelector('.sidebar-overlay');
        const sidebar = document.querySelector('.sidebar-menu');

        if (overlay && sidebar) {
            overlay.classList.remove('active');
            sidebar.classList.remove('active');
            document.body.classList.remove('no-scroll');
        }
    },

    // 아코디언 토글
    toggleAccordion(element) {
        const accordion = element.closest('.accordion, .accordion-item');
        if (!accordion) return;

        const isOpen = accordion.classList.contains('open');

        // 다른 아코디언 닫기 (선택적)
        // document.querySelectorAll('.accordion.open, .accordion-item.open').forEach(acc => {
        //     if (acc !== accordion) acc.classList.remove('open');
        // });

        accordion.classList.toggle('open');
    },

    // 체크박스 토글
    toggleCheckbox(element) {
        const item = element.closest('.checkbox-item');
        if (!item) return;

        item.classList.toggle('checked');
        const input = item.querySelector('input[type="checkbox"]');
        if (input) {
            input.checked = item.classList.contains('checked');
        }
    },

    // 설문 옵션 선택
    selectSurveyOption(element) {
        const container = element.closest('.survey-options');
        if (!container) return;

        const isMultiple = container.dataset.multiple === 'true';

        if (!isMultiple) {
            container.querySelectorAll('.survey-option').forEach(opt => {
                opt.classList.remove('selected');
            });
        }

        element.classList.toggle('selected');
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

    // 파일 업로드
    initFileUpload(uploadAreaId, options = {}) {
        const area = document.getElementById(uploadAreaId);
        if (!area) return;

        const input = area.querySelector('input[type="file"]');

        // 드래그 앤 드롭
        area.addEventListener('dragover', (e) => {
            e.preventDefault();
            area.classList.add('dragover');
        });

        area.addEventListener('dragleave', () => {
            area.classList.remove('dragover');
        });

        area.addEventListener('drop', (e) => {
            e.preventDefault();
            area.classList.remove('dragover');

            const files = e.dataTransfer.files;
            if (files.length && input) {
                input.files = files;
                input.dispatchEvent(new Event('change'));
            }
        });

        // 클릭으로 파일 선택
        area.addEventListener('click', () => {
            if (input) input.click();
        });

        // 파일 선택 시 미리보기
        if (input && options.onSelect) {
            input.addEventListener('change', () => {
                if (input.files.length) {
                    options.onSelect(input.files);
                }
            });
        }
    },

    // 이미지 미리보기
    previewImage(file, previewElement) {
        if (!file || !previewElement) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            if (typeof previewElement === 'string') {
                previewElement = document.querySelector(previewElement);
            }
            if (previewElement) {
                if (previewElement.tagName === 'IMG') {
                    previewElement.src = e.target.result;
                } else {
                    previewElement.style.backgroundImage = `url(${e.target.result})`;
                }
            }
        };
        reader.readAsDataURL(file);
    },

    // 자동 저장
    initAutoSave(formId, saveUrl, interval = 30000) {
        const form = document.getElementById(formId);
        if (!form) return;

        let saveTimeout;

        const save = async () => {
            const data = this.getFormData(formId);
            try {
                await this.api(saveUrl, {
                    method: 'POST',
                    body: data
                });
                console.log('자동 저장 완료');
            } catch (error) {
                console.error('자동 저장 실패:', error);
            }
        };

        // 입력 시 디바운스 저장
        form.addEventListener('input', () => {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(save, interval);
        });

        // 페이지 떠날 때 저장
        window.addEventListener('beforeunload', save);
    },

    // D-Day 계산
    calculateDday(targetDate) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const target = new Date(targetDate);
        target.setHours(0, 0, 0, 0);

        const diff = Math.ceil((target - today) / (1000 * 60 * 60 * 24));

        if (diff === 0) return { text: 'D-Day', days: 0, isPast: false };
        if (diff > 0) return { text: `D-${diff}`, days: diff, isPast: false };
        return { text: `D+${Math.abs(diff)}`, days: Math.abs(diff), isPast: true };
    },

    // 날짜 포맷팅
    formatDate(date, format = 'YYYY년 MM월 DD일') {
        if (!date) return '';
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const weekdays = ['일', '월', '화', '수', '목', '금', '토'];
        const weekday = weekdays[d.getDay()];

        return format
            .replace('YYYY', year)
            .replace('MM', month)
            .replace('DD', day)
            .replace('W', weekday);
    },

    // 전화 걸기
    callPhone(phoneNumber) {
        window.location.href = `tel:${phoneNumber}`;
    },

    // 카카오톡 연결
    openKakao(kakaoId) {
        window.open(`https://pf.kakao.com/_${kakaoId}`, '_blank');
    },

    // 외부 링크 열기
    openLink(url) {
        if (url) {
            window.open(url, '_blank');
        }
    },

    // 리플 효과
    addRipple(element, event) {
        const ripple = document.createElement('span');
        ripple.className = 'ripple';

        const rect = element.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;

        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;

        element.appendChild(ripple);

        setTimeout(() => ripple.remove(), 600);
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

    // 스크롤 위치 저장/복원
    saveScrollPosition(key = 'scrollPos') {
        sessionStorage.setItem(key, window.scrollY);
    },

    restoreScrollPosition(key = 'scrollPos') {
        const pos = sessionStorage.getItem(key);
        if (pos) {
            window.scrollTo(0, parseInt(pos));
            sessionStorage.removeItem(key);
        }
    },

    // 모바일 체크
    isMobile() {
        return window.innerWidth <= 1024;
    },

    // 카카오톡 문의 (플로팅 버튼)
    openKakaoContact() {
        const contact = window.BORN_CONTACT || {};
        if (this.isMobile()) {
            // 모바일: 카카오톡 채널로 바로 이동
            window.location.href = contact.kakaoUrl || 'https://pf.kakao.com';
        } else {
            // PC: 모달로 안내
            document.getElementById('contactModalContent').innerHTML = `
                <div class="modal-icon kakao">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 3C6.5 3 2 6.58 2 11c0 2.8 1.87 5.26 4.67 6.67-.15.57-.52 2.05-.6 2.37-.1.4.15.39.31.28.13-.08 2.03-1.37 2.85-1.93.89.14 1.82.21 2.77.21 5.5 0 10-3.58 10-8s-4.5-8-10-8z"/>
                    </svg>
                </div>
                <h3>카카오톡 문의</h3>
                <p>아래 정보로 카카오톡 채널을 검색해주세요.</p>
                <div class="modal-info">
                    <span class="label">카카오톡 채널</span>
                    <span class="value">${contact.kakao || ''}</span>
                </div>
                <a href="${contact.kakaoUrl || '#'}" target="_blank" class="btn btn-kakao">
                    카카오톡 채널 바로가기
                </a>
            `;
            document.getElementById('contactModal').classList.add('active');
        }
    },

    // 전화 문의 (플로팅 버튼)
    openPhoneContact() {
        const contact = window.BORN_CONTACT || {};
        if (this.isMobile()) {
            // 모바일: 바로 전화
            window.location.href = `tel:${contact.phone || ''}`;
        } else {
            // PC: 모달로 안내
            document.getElementById('contactModalContent').innerHTML = `
                <div class="modal-icon phone">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                </div>
                <h3>전화 문의</h3>
                <p>아래 번호로 전화주시면 친절히 상담해드립니다.</p>
                <div class="modal-info">
                    <span class="label">대표번호</span>
                    <span class="value">${contact.phone || ''}</span>
                </div>
                <div class="modal-hours">
                    <p><strong>운영 시간</strong></p>
                    <p>평일 09:00 - 18:00 (점심 12:00 - 13:00)</p>
                    <p>주말/공휴일 휴무</p>
                </div>
            `;
            document.getElementById('contactModal').classList.add('active');
        }
    },

    // 연락처 모달 닫기
    closeContactModal() {
        const modal = document.getElementById('contactModal');
        if (modal) {
            modal.classList.remove('active');
        }
    }
};

// DOM 로드 완료 시 초기화
document.addEventListener('DOMContentLoaded', () => {
    // 사이드바 이벤트
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => BornUser.closeSidebar());
    }

    // 메뉴 버튼 클릭
    const menuBtn = document.querySelector('.header-menu');
    if (menuBtn) {
        menuBtn.addEventListener('click', () => BornUser.openSidebar());
    }

    // 아코디언 클릭
    document.querySelectorAll('.accordion-header, .accordion-item .accordion-header').forEach(header => {
        header.addEventListener('click', function(e) {
            e.preventDefault();
            BornUser.toggleAccordion(this);
        });
    });

    // 체크박스 아이템 클릭
    document.querySelectorAll('.checkbox-item').forEach(item => {
        item.addEventListener('click', () => BornUser.toggleCheckbox(item));
    });

    // 설문 옵션 클릭
    document.querySelectorAll('.survey-option').forEach(option => {
        option.addEventListener('click', () => BornUser.selectSurveyOption(option));
    });

    // 리플 효과
    document.querySelectorAll('.ripple-effect').forEach(el => {
        el.addEventListener('click', (e) => BornUser.addRipple(el, e));
    });

    // 페이지 전환 애니메이션
    document.querySelectorAll('.page-enter').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        requestAnimationFrame(() => {
            el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        });
    });

    // 뒤로 가기 버튼
    document.querySelectorAll('.header-back').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            // referrer가 같은 도메인이고 /born 경로를 포함하는 경우에만 뒤로가기
            if (document.referrer &&
                document.referrer.includes(window.location.host) &&
                document.referrer.includes('/')) {
                history.back();
            } else {
                window.location.href = '/user/main.php';
            }
        });
    });

    // ESC 키로 모달 닫기
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            BornUser.closeContactModal();
        }
    });
});

// 전역으로 노출
window.BornUser = BornUser;
