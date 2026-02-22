<?php
/**
 * 본투어 인터내셔날 - 관리자 대시보드
 */

$pageTitle = '대시보드';
require_once __DIR__ . '/../includes/header.php';

// 통계 데이터 가져오기
$db = db();

// 전체 행사 수
$stmt = $db->query("SELECT COUNT(*) as total FROM events");
$totalEvents = $stmt->fetch()['total'];

// 활성 행사 수
$stmt = $db->query("SELECT COUNT(*) as total FROM events WHERE status = 'active'");
$activeEvents = $stmt->fetch()['total'];

// 전체 회원 수
$stmt = $db->query("SELECT COUNT(*) as total FROM members");
$totalMembers = $stmt->fetch()['total'];

// 오늘 가입 회원 수
$stmt = $db->query("SELECT COUNT(*) as total FROM members WHERE DATE(created_at) = CURDATE()");
$todayMembers = $stmt->fetch()['total'];

// 여권 제출 현황
$stmt = $db->query("SELECT COUNT(*) as total FROM passports");
$totalPassports = $stmt->fetch()['total'];

// 설문 응답 수
$stmt = $db->query("SELECT COUNT(DISTINCT survey_id, member_id) as total FROM survey_completions");
$totalSurveyResponses = $stmt->fetch()['total'];

// 최근 행사 목록
$stmt = $db->query("
    SELECT e.*,
           (SELECT COUNT(*) FROM event_members WHERE event_id = e.id) as member_count
    FROM events e
    WHERE e.status = 'active'
    ORDER BY e.start_date ASC
    LIMIT 5
");
$recentEvents = $stmt->fetchAll();

// 최근 가입 회원
$stmt = $db->query("
    SELECT m.*,
           (SELECT GROUP_CONCAT(e.event_name SEPARATOR ', ')
            FROM event_members em
            JOIN events e ON em.event_id = e.id
            WHERE em.member_id = m.id) as events
    FROM members m
    ORDER BY m.created_at DESC
    LIMIT 5
");
$recentMembers = $stmt->fetchAll();
?>

<!-- 통계 카드 -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
        </div>
        <div class="stat-info">
            <h3><?= number_format($activeEvents) ?></h3>
            <p>활성 행사</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon accent">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <div class="stat-info">
            <h3><?= number_format($totalMembers) ?></h3>
            <p>전체 회원<?php if ($todayMembers > 0): ?> <span style="color: var(--success); font-size: 12px;">(+<?= $todayMembers ?> 오늘)</span><?php endif; ?></p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="16" rx="2"/>
                <circle cx="12" cy="10" r="3"/>
                <line x1="7" y1="16" x2="17" y2="16"/>
            </svg>
        </div>
        <div class="stat-info">
            <h3><?= number_format($totalPassports) ?></h3>
            <p>여권 제출</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon warning">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 11l3 3L22 4"/>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
        </div>
        <div class="stat-info">
            <h3><?= number_format($totalSurveyResponses) ?></h3>
            <p>설문 응답</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- 최근 행사 -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">진행 중인 행사</h3>
            <a href="/born/admin/event-editor.php" class="btn btn-sm btn-outline">전체보기</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($recentEvents)): ?>
                <div style="padding: 40px 20px; text-align: center; color: var(--gray-500);">
                    등록된 행사가 없습니다.
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>행사명</th>
                                <th>기간</th>
                                <th>인원</th>
                                <th>D-Day</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentEvents as $event): ?>
                                <?php $dday = calculate_dday($event['start_date']); ?>
                                <tr>
                                    <td>
                                        <a href="/born/admin/event-editor.php?id=<?= $event['id'] ?>" style="color: var(--gray-900); font-weight: 500;">
                                            <?= h($event['event_name']) ?>
                                        </a>
                                    </td>
                                    <td style="font-size: 13px; color: var(--gray-600);">
                                        <?= format_date_short($event['start_date']) ?> ~ <?= format_date_short($event['end_date']) ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary"><?= $event['member_count'] ?>명</span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $dday['isPast'] ? 'badge-gray' : 'badge-success' ?>">
                                            <?= $dday['text'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 최근 가입 회원 -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">최근 가입 회원</h3>
            <a href="/born/admin/members.php" class="btn btn-sm btn-outline">전체보기</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($recentMembers)): ?>
                <div style="padding: 40px 20px; text-align: center; color: var(--gray-500);">
                    등록된 회원이 없습니다.
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>이름</th>
                                <th>연락처</th>
                                <th>참여 행사</th>
                                <th>가입일</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentMembers as $member): ?>
                                <tr>
                                    <td style="font-weight: 500;"><?= h($member['name_ko']) ?></td>
                                    <td style="font-size: 13px; color: var(--gray-600);">
                                        <?= $member['phone'] ? format_phone($member['phone']) : '-' ?>
                                    </td>
                                    <td style="font-size: 13px; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= h($member['events'] ?? '-') ?>
                                    </td>
                                    <td style="font-size: 13px; color: var(--gray-500);">
                                        <?= date('m.d H:i', strtotime($member['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 빠른 작업 -->
<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <h3 class="card-title">빠른 작업</h3>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="/born/admin/event-editor.php?action=new" class="btn btn-primary">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                새 행사 등록
            </a>
            <a href="/born/admin/members.php?action=new" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <line x1="20" y1="8" x2="20" y2="14"/>
                    <line x1="23" y1="11" x2="17" y2="11"/>
                </svg>
                회원 추가
            </a>
            <a href="/born/admin/survey-editor.php?action=new" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="12" y1="18" x2="12" y2="12"/>
                    <line x1="9" y1="15" x2="15" y2="15"/>
                </svg>
                설문 만들기
            </a>
            <a href="/born/admin/passport.php" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="16" rx="2"/>
                    <circle cx="12" cy="10" r="3"/>
                    <line x1="7" y1="16" x2="17" y2="16"/>
                </svg>
                여권 확인
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
