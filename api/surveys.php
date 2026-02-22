<?php
/**
 * 본투어 인터내셔날 - 설문 API
 */

require_once __DIR__ . '/../includes/auth.php';

// JSON 입력 처리
$jsonInput = json_decode(file_get_contents('php://input'), true) ?? [];

// JSON 입력을 $_POST에 병합
if (!empty($jsonInput)) {
    $_POST = array_merge($_POST, $jsonInput);
}

$action = $jsonInput['action'] ?? input('action');

$adminActions = ['create', 'update', 'delete', 'add_question', 'update_question', 'delete_question', 'add_page', 'delete_page'];
if (in_array($action, $adminActions)) {
    require_admin_auth();
}

switch ($action) {
    case 'get': getSurvey(); break;
    case 'get_question': getQuestion(); break;
    case 'list': getSurveys(); break;
    case 'create': createSurvey(); break;
    case 'update': updateSurvey(); break;
    case 'delete': deleteSurvey(); break;
    case 'add_question': addQuestion(); break;
    case 'update_question': updateQuestion(); break;
    case 'delete_question': deleteQuestion(); break;
    case 'add_page': addPage(); break;
    case 'delete_page': deletePage(); break;
    case 'submit': submitSurvey(); break;
    case 'save_draft': saveDraft(); break;
    case 'get_draft': getDraft(); break;
    default: json_error('잘못된 요청입니다.', 400);
}

function getSurvey(): void {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM surveys WHERE id = ?");
    $stmt->execute([input('id')]);
    $survey = $stmt->fetch();

    if ($survey) {
        $stmt = $db->prepare("SELECT * FROM survey_pages WHERE survey_id = ? ORDER BY page_order");
        $stmt->execute([$survey['id']]);
        $survey['pages'] = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY page_id, question_order");
        $stmt->execute([$survey['id']]);
        $survey['questions'] = $stmt->fetchAll();
    }

    json_success($survey);
}

function getQuestion(): void {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM survey_questions WHERE id = ?");
    $stmt->execute([input('id')]);
    json_success($stmt->fetch());
}

function getSurveys(): void {
    $db = db();
    $eventId = input('event_id');

    $sql = "SELECT * FROM surveys WHERE status = 'active'";
    $params = [];

    if ($eventId) {
        $sql .= " AND (event_id = ? OR event_id IS NULL)";
        $params[] = $eventId;
    }

    $sql .= " ORDER BY created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    json_success($stmt->fetchAll());
}

function createSurvey(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $db = db();
    $stmt = $db->prepare("INSERT INTO surveys (title, event_id) VALUES (?, ?)");
    $stmt->execute([input('title'), input('event_id') ?: null]);
    $surveyId = $db->lastInsertId();

    // 기본 페이지 생성
    $stmt = $db->prepare("INSERT INTO survey_pages (survey_id, page_order) VALUES (?, 1)");
    $stmt->execute([$surveyId]);

    json_success(['id' => $surveyId], '설문이 생성되었습니다.');
}

function updateSurvey(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $db = db();
    $stmt = $db->prepare("UPDATE surveys SET title = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?");
    $stmt->execute([
        input('title'),
        input('start_date') ?: null,
        input('end_date') ?: null,
        input('status', 'active'),
        input('id')
    ]);
    json_success(null, '저장되었습니다.');
}

function deleteSurvey(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $db = db();
    $stmt = $db->prepare("DELETE FROM surveys WHERE id = ?");
    $stmt->execute([input('id')]);
    json_success(null, '삭제되었습니다.');
}

function addQuestion(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $db = db();
    $surveyId = input('survey_id');
    $pageId = input('page_id');

    // 페이지 ID가 없으면 첫 번째 페이지 사용
    if (!$pageId) {
        $stmt = $db->prepare("SELECT id FROM survey_pages WHERE survey_id = ? ORDER BY page_order LIMIT 1");
        $stmt->execute([$surveyId]);
        $page = $stmt->fetch();

        if (!$page) {
            // 페이지 없으면 생성
            $stmt = $db->prepare("INSERT INTO survey_pages (survey_id, page_order) VALUES (?, 1)");
            $stmt->execute([$surveyId]);
            $pageId = $db->lastInsertId();
        } else {
            $pageId = $page['id'];
        }
    }

    $stmt = $db->prepare("INSERT INTO survey_questions (survey_id, page_id, question_type, question_text, options) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $surveyId,
        $pageId,
        input('question_type', 'multiple'),
        input('question_text'),
        input('options')
    ]);

    json_success(['id' => $db->lastInsertId()], '질문이 추가되었습니다.');
}

function updateQuestion(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $db = db();
    $stmt = $db->prepare("UPDATE survey_questions SET page_id = ?, question_type = ?, question_text = ?, options = ? WHERE id = ?");
    $stmt->execute([
        input('page_id'),
        input('question_type'),
        input('question_text'),
        input('options'),
        input('id')
    ]);
    json_success(null, '수정되었습니다.');
}

function deleteQuestion(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $db = db();
    $stmt = $db->prepare("DELETE FROM survey_questions WHERE id = ?");
    $stmt->execute([input('id')]);
    json_success(null, '삭제되었습니다.');
}

function addPage(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $db = db();
    $surveyId = input('survey_id');

    // 현재 최대 페이지 순서 가져오기
    $stmt = $db->prepare("SELECT MAX(page_order) as max_order FROM survey_pages WHERE survey_id = ?");
    $stmt->execute([$surveyId]);
    $result = $stmt->fetch();
    $newOrder = ($result['max_order'] ?? 0) + 1;

    $stmt = $db->prepare("INSERT INTO survey_pages (survey_id, page_order) VALUES (?, ?)");
    $stmt->execute([$surveyId, $newOrder]);

    json_success(['id' => $db->lastInsertId()], '페이지가 추가되었습니다.');
}

function deletePage(): void {
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $db = db();
    $pageId = input('id');

    // 해당 페이지의 질문들도 함께 삭제됨 (CASCADE)
    $stmt = $db->prepare("DELETE FROM survey_pages WHERE id = ?");
    $stmt->execute([$pageId]);

    json_success(null, '삭제되었습니다.');
}

function submitSurvey(): void {
    require_user_auth();
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $user = get_logged_in_user();

    // JSON 입력 처리
    $input = json_decode(file_get_contents('php://input'), true);
    $surveyId = $input['survey_id'] ?? input('survey_id');
    $answers = $input['answers'] ?? input('answers');

    if (!is_array($answers)) {
        json_error('응답 데이터가 올바르지 않습니다.');
    }

    $db = db();

    // 기존 응답 삭제
    $stmt = $db->prepare("DELETE FROM survey_responses WHERE survey_id = ? AND member_id = ?");
    $stmt->execute([$surveyId, $user['id']]);

    // 새 응답 저장
    $stmt = $db->prepare("INSERT INTO survey_responses (survey_id, member_id, question_id, answer) VALUES (?, ?, ?, ?)");
    foreach ($answers as $questionId => $answer) {
        $stmt->execute([$surveyId, $user['id'], $questionId, $answer]);
    }

    // 완료 기록
    $stmt = $db->prepare("INSERT INTO survey_completions (survey_id, member_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE completed_at = NOW()");
    $stmt->execute([$surveyId, $user['id']]);

    // 임시저장 삭제
    $stmt = $db->prepare("DELETE FROM survey_drafts WHERE survey_id = ? AND member_id = ?");
    $stmt->execute([$surveyId, $user['id']]);

    json_success(null, '설문이 제출되었습니다.');
}

function saveDraft(): void {
    require_user_auth();
    if (!is_post()) json_error('잘못된 요청입니다.', 405);

    $user = get_logged_in_user();

    // JSON 입력 처리
    $input = json_decode(file_get_contents('php://input'), true);
    $surveyId = $input['survey_id'] ?? input('survey_id');
    $answers = $input['answers'] ?? input('answers');
    $draftData = json_encode($answers);

    $db = db();
    $stmt = $db->prepare("INSERT INTO survey_drafts (survey_id, member_id, draft_data) VALUES (?, ?, ?)
                          ON DUPLICATE KEY UPDATE draft_data = ?");
    $stmt->execute([$surveyId, $user['id'], $draftData, $draftData]);

    json_success(null);
}

function getDraft(): void {
    require_user_auth();

    $user = get_logged_in_user();
    $surveyId = input('survey_id');

    $db = db();
    $stmt = $db->prepare("SELECT draft_data FROM survey_drafts WHERE survey_id = ? AND member_id = ?");
    $stmt->execute([$surveyId, $user['id']]);
    $draft = $stmt->fetch();

    json_success($draft ? json_decode($draft['draft_data'], true) : null);
}
