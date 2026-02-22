<?php
/**
 * 본투어 인터내셔날 - 설문 통계
 */

$pageTitle = '설문 통계 확인하기';
require_once __DIR__ . '/../includes/header.php';

$db = db();

// 설문 목록
$stmt = $db->query("SELECT id, title FROM surveys ORDER BY created_at DESC");
$surveys = $stmt->fetchAll();

$surveyId = input('id');
$survey = null;
$questions = [];
$totalResponses = 0;

if ($surveyId) {
    // 설문 정보
    $stmt = $db->prepare("SELECT * FROM surveys WHERE id = ?");
    $stmt->execute([$surveyId]);
    $survey = $stmt->fetch();

    if ($survey) {
        // 질문 및 응답 통계
        $stmt = $db->prepare("
            SELECT sq.*,
                   (SELECT COUNT(*) FROM survey_responses WHERE question_id = sq.id) as response_count
            FROM survey_questions sq
            WHERE sq.survey_id = ?
            ORDER BY sq.page_id, sq.question_order
        ");
        $stmt->execute([$surveyId]);
        $questions = $stmt->fetchAll();

        // 응답자 수
        $stmt = $db->prepare("SELECT COUNT(DISTINCT member_id) as total FROM survey_completions WHERE survey_id = ?");
        $stmt->execute([$surveyId]);
        $totalResponses = $stmt->fetch()['total'];

        // 각 질문별 응답 가져오기
        foreach ($questions as &$q) {
            $stmt = $db->prepare("SELECT answer, COUNT(*) as count FROM survey_responses WHERE question_id = ? GROUP BY answer ORDER BY count DESC");
            $stmt->execute([$q['id']]);
            $q['responses'] = $stmt->fetchAll();
        }
    }
}

// 객관식 / 단답형&서술형 분리
$multipleQuestions = [];
$textQuestions = [];
if (!empty($questions)) {
    foreach ($questions as $q) {
        if ($q['question_type'] === 'multiple') {
            $multipleQuestions[] = $q;
        } else {
            $textQuestions[] = $q;
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">설문 통계 확인하기</h3>
        <a href="/born/admin/" class="btn btn-sm btn-ghost btn-icon" title="메인으로">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </a>
    </div>
    <div class="card-body">
        <!-- 설문 선택 -->
        <div class="form-group" style="max-width: 400px; margin-bottom: 32px;">
            <label class="form-label">설문 선택</label>
            <select class="form-select" onchange="if(this.value) location.href='?id='+this.value">
                <option value="">설문을 선택하세요</option>
                <?php foreach ($surveys as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $surveyId == $s['id'] ? 'selected' : '' ?>>
                        <?= h($s['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($survey): ?>
            <?php if (empty($questions)): ?>
                <p style="text-align: center; color: var(--gray-500); padding: 40px;">등록된 질문이 없습니다.</p>
            <?php else: ?>
                <!-- 통계 요약 -->
                <div style="margin-bottom: 24px; padding: 16px; background: var(--gray-50); border-radius: var(--radius-md);">
                    <span style="font-weight: 600;">총 응답자: <?= number_format($totalResponses) ?>명</span>
                    <span style="margin-left: 24px;">질문 수: <?= count($questions) ?>개</span>
                </div>

                <!-- 두 컬럼 레이아웃 -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
                    <!-- 좌측: 객관식 질문들 -->
                    <div>
                        <?php foreach ($multipleQuestions as $idx => $q): ?>
                            <div style="margin-bottom: 32px;">
                                <h4 style="font-size: 15px; font-weight: 600; margin-bottom: 16px;">
                                    질문 <?= str_pad($idx + 1, 2, '0', STR_PAD_LEFT) ?> (객관식)
                                </h4>
                                <p style="font-size: 14px; color: var(--gray-600); margin-bottom: 16px;">
                                    <?= h($q['question_text']) ?>
                                </p>

                                <?php if (!empty($q['responses'])): ?>
                                    <?php
                                    $options = json_decode($q['options'], true) ?: [];
                                    $responseMap = [];
                                    foreach ($q['responses'] as $r) {
                                        $responseMap[$r['answer']] = $r['count'];
                                    }
                                    $total = array_sum($responseMap);
                                    $colors = ['#3949ab', '#e07a5f', '#4caf50', '#ff9800', '#9c27b0', '#00bcd4'];
                                    ?>
                                    <div style="display: flex; align-items: center; gap: 24px;">
                                        <!-- 도넛 차트 -->
                                        <div style="width: 150px; height: 150px; position: relative; flex-shrink: 0;">
                                            <canvas id="chart-<?= $q['id'] ?>" width="150" height="150"></canvas>
                                        </div>
                                        <!-- 범례 -->
                                        <div style="flex: 1; font-size: 13px;">
                                            <?php $colorIdx = 0; ?>
                                            <?php foreach ($options as $opt): ?>
                                                <?php
                                                $count = $responseMap[$opt] ?? 0;
                                                $percent = $total > 0 ? round(($count / $total) * 100) : 0;
                                                $color = $colors[$colorIdx % count($colors)];
                                                $colorIdx++;
                                                ?>
                                                <div style="display: flex; align-items: center; margin-bottom: 6px;">
                                                    <span style="width: 10px; height: 10px; background: <?= $color ?>; border-radius: 2px; margin-right: 8px; flex-shrink: 0;"></span>
                                                    <span style="flex: 1;"><?= h($opt) ?></span>
                                                    <span style="font-weight: 600; margin-left: 8px;"><?= $percent ?>%</span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <script>
                                    (function() {
                                        const canvas = document.getElementById('chart-<?= $q['id'] ?>');
                                        const ctx = canvas.getContext('2d');
                                        const data = <?= json_encode(array_map(function($opt) use ($responseMap) {
                                            return $responseMap[$opt] ?? 0;
                                        }, $options)) ?>;
                                        const colors = ['#3949ab', '#e07a5f', '#4caf50', '#ff9800', '#9c27b0', '#00bcd4'];
                                        const total = data.reduce((a, b) => a + b, 0);

                                        if (total === 0) return;

                                        let startAngle = -Math.PI / 2;
                                        const centerX = 75, centerY = 75, radius = 60, innerRadius = 35;

                                        data.forEach((value, i) => {
                                            if (value === 0) return;
                                            const sliceAngle = (value / total) * 2 * Math.PI;

                                            ctx.beginPath();
                                            ctx.moveTo(centerX + innerRadius * Math.cos(startAngle), centerY + innerRadius * Math.sin(startAngle));
                                            ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
                                            ctx.arc(centerX, centerY, innerRadius, startAngle + sliceAngle, startAngle, true);
                                            ctx.closePath();
                                            ctx.fillStyle = colors[i % colors.length];
                                            ctx.fill();

                                            startAngle += sliceAngle;
                                        });
                                    })();
                                    </script>
                                <?php else: ?>
                                    <p style="color: var(--gray-500); font-size: 14px;">아직 응답이 없습니다.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($multipleQuestions)): ?>
                            <p style="color: var(--gray-500); font-size: 14px;">객관식 질문이 없습니다.</p>
                        <?php endif; ?>
                    </div>

                    <!-- 우측: 단답형/서술형 질문들 -->
                    <div>
                        <?php foreach ($textQuestions as $idx => $q): ?>
                            <div style="margin-bottom: 32px;">
                                <h4 style="font-size: 15px; font-weight: 600; margin-bottom: 16px;">
                                    질문 <?= str_pad($idx + 1, 2, '0', STR_PAD_LEFT) ?> (<?= $q['question_type'] === 'short' ? '단답형' : '서술형' ?>)
                                </h4>
                                <p style="font-size: 14px; color: var(--gray-600); margin-bottom: 16px;">
                                    <?= h($q['question_text']) ?>
                                </p>

                                <?php if (!empty($q['responses'])): ?>
                                    <div style="border: 1px solid var(--gray-200); border-radius: var(--radius-md); overflow: hidden;">
                                        <table style="width: 100%; border-collapse: collapse;">
                                            <?php foreach (array_slice($q['responses'], 0, 4) as $r): ?>
                                                <tr style="border-bottom: 1px solid var(--gray-200);">
                                                    <td style="padding: 12px 16px; font-size: 14px;">
                                                        <?= h(mb_substr($r['answer'], 0, 30)) ?><?= mb_strlen($r['answer']) > 30 ? '...' : '' ?>
                                                    </td>
                                                    <td style="padding: 12px 16px; width: 100px; text-align: right;">
                                                        <button type="button" class="btn btn-sm btn-primary" onclick="showFullAnswer('<?= h(addslashes($r['answer'])) ?>')">
                                                            전체보기
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    </div>
                                    <?php if (count($q['responses']) > 4): ?>
                                        <button type="button" class="btn btn-sm btn-secondary" style="margin-top: 12px;" onclick="showAllResponses(<?= $q['id'] ?>, '<?= h(addslashes($q['question_text'])) ?>')">
                                            전체 응답 보기 (<?= count($q['responses']) ?>개)
                                        </button>
                                        <div id="responses-data-<?= $q['id'] ?>" style="display: none;">
                                            <?= h(json_encode($q['responses'])) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p style="color: var(--gray-500); font-size: 14px;">아직 응답이 없습니다.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($textQuestions)): ?>
                            <p style="color: var(--gray-500); font-size: 14px;">단답형/서술형 질문이 없습니다.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p style="text-align: center; color: var(--gray-500); padding: 60px;">설문을 선택해주세요.</p>
        <?php endif; ?>
    </div>
</div>

<!-- 전체 응답 보기 모달 -->
<div class="modal-backdrop" id="responses-modal">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title" id="responses-modal-title">응답 전체보기</h3>
            <span class="modal-close" onclick="BornAdmin.closeModal('responses-modal')">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </span>
        </div>
        <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
            <div id="responses-modal-content"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="BornAdmin.closeModal('responses-modal')">닫기</button>
        </div>
    </div>
</div>

<!-- 단일 응답 보기 모달 -->
<div class="modal-backdrop" id="answer-modal">
    <div class="modal" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title">응답 내용</h3>
            <span class="modal-close" onclick="BornAdmin.closeModal('answer-modal')">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </span>
        </div>
        <div class="modal-body">
            <div id="answer-modal-content" style="white-space: pre-wrap; font-size: 14px; line-height: 1.6;"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="BornAdmin.closeModal('answer-modal')">닫기</button>
        </div>
    </div>
</div>

<script>
function showFullAnswer(answer) {
    document.getElementById('answer-modal-content').textContent = answer;
    BornAdmin.openModal('answer-modal');
}

function showAllResponses(questionId, questionText) {
    const dataEl = document.getElementById('responses-data-' + questionId);
    if (!dataEl) return;

    const responses = JSON.parse(dataEl.textContent);
    document.getElementById('responses-modal-title').textContent = questionText;

    let html = '<div style="border: 1px solid var(--gray-200); border-radius: var(--radius-md); overflow: hidden;">';
    html += '<table style="width: 100%; border-collapse: collapse;">';
    responses.forEach(r => {
        html += `<tr style="border-bottom: 1px solid var(--gray-200);">
            <td style="padding: 12px 16px; font-size: 14px;">${escapeHtml(r.answer)}</td>
            <td style="padding: 12px 16px; width: 80px; text-align: center; color: var(--gray-500);">${r.count}명</td>
        </tr>`;
    });
    html += '</table></div>';

    document.getElementById('responses-modal-content').innerHTML = html;
    BornAdmin.openModal('responses-modal');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
