<?php
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';
$pdo = Database::connect();

function generateRequestCode() {
    return 'REQ' . date('YmdHis') . rand(1000, 9999);
}

function generateReceiptCode() {
    return 'RCP' . date('YmdHis') . rand(1000, 9999);
}

function getRetentionReasons() {
    return [
        'aggregated_reports' => '根据《网络安全法》和《个人信息保护法》相关规定，匿名化处理后的汇总统计报表属于法定留存范围，用于系统安全审计和服务质量监控。此类数据已去除所有可识别个人身份的信息，无法关联到具体自然人。'
    ];
}

try {
    switch ($action) {
        case 'collect':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $input = json_decode(file_get_contents('php://input'), true);

            $ip = $_SERVER['REMOTE_ADDR'];
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
            } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
                $ip = $_SERVER['HTTP_X_REAL_IP'];
            }
            $ip = trim($ip);

            $country = $input['country'] ?? '';
            $city = $input['city'] ?? '';
            $isp = $input['isp'] ?? '';

            if (empty($country) && empty($city)) {
                $geoUrl = "http://ip-api.com/json/{$ip}?lang=zh-CN";
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 3,
                        'ignore_errors' => true
                    ]
                ]);
                $geoJson = @file_get_contents($geoUrl, false, $context);
                if ($geoJson) {
                    $geoData = json_decode($geoJson, true);
                    if ($geoData && $geoData['status'] === 'success') {
                        $country = $geoData['country'] ?? '';
                        $city = $geoData['city'] ?? '';
                        $isp = $geoData['isp'] ?? '';
                    }
                }
            }

            $data = [
                ':ip' => $ip,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                ':country' => $country,
                ':city' => $city,
                ':isp' => $isp,

                ':browser' => $input['browser'] ?? '未知',
                ':browser_version' => $input['browser_version'] ?? '',
                ':os' => $input['os'] ?? '未知',
                ':os_version' => $input['os_version'] ?? '',
                ':device_type' => $input['device_type'] ?? '桌面设备',

                ':screen_width' => $input['screen_width'] ?? 0,
                ':screen_height' => $input['screen_height'] ?? 0,
                ':window_width' => $input['window_width'] ?? 0,
                ':window_height' => $input['window_height'] ?? 0,

                ':language' => $input['language'] ?? '',
                ':timezone' => $input['timezone'] ?? '',
                ':platform' => $input['platform'] ?? '',
                ':cookie_enabled' => isset($input['cookie_enabled']) ? ($input['cookie_enabled'] ? 1 : 0) : 0,

                ':touch_points' => $input['touch_points'] ?? 0,
                ':device_memory' => $input['device_memory'] ?? 0,
                ':cpu_cores' => $input['cpu_cores'] ?? 0,
                ':connection_type' => $input['connection_type'] ?? '',

                ':referrer' => $input['referrer'] ?? '',
                ':remark' => '',
                ':email' => $input['email'] ?? ''
            ];

            $sql = "INSERT INTO visitors (
                ip, user_agent, country, city, isp,
                browser, browser_version, os, os_version, device_type,
                screen_width, screen_height, window_width, window_height,
                language, timezone, platform, cookie_enabled,
                touch_points, device_memory, cpu_cores, connection_type,
                referrer, remark, email
            ) VALUES (
                :ip, :user_agent, :country, :city, :isp,
                :browser, :browser_version, :os, :os_version, :device_type,
                :screen_width, :screen_height, :window_width, :window_height,
                :language, :timezone, :platform, :cookie_enabled,
                :touch_points, :device_memory, :cpu_cores, :connection_type,
                :referrer, :remark, :email
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);

            echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
            break;

        case 'list':
            Auth::requireLogin();

            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;
            $search = $_GET['search'] ?? '';

            $where = "WHERE 1=1 AND is_deleted = 0";
            $params = [];

            if ($search) {
                $where .= " AND (ip LIKE :search OR remark LIKE :search OR city LIKE :search OR email LIKE :search)";
                $params[':search'] = "%$search%";
            }

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM visitors $where");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT * FROM visitors $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($params);
            $list = $stmt->fetchAll();

            echo json_encode([
                'status' => 'success',
                'data' => $list,
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / $limit)
            ]);
            break;

        case 'remark':
            Auth::requireLogin();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? 0;
            $remark = $input['remark'] ?? '';

            if (!$id)
                throw new Exception('ID required');

            $stmt = $pdo->prepare("UPDATE visitors SET remark = :remark WHERE id = :id");
            $stmt->execute([':remark' => $remark, ':id' => $id]);

            echo json_encode(['status' => 'success']);
            break;

        case 'stats':
            Auth::requireLogin();

            $today = date('Y-m-d');
            $todayStmt = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE DATE(created_at) = :today AND is_deleted = 0");
            $todayStmt->execute([':today' => $today]);
            $todayCount = $todayStmt->fetchColumn();

            $totalStmt = $pdo->query("SELECT COUNT(*) FROM visitors WHERE is_deleted = 0");
            $totalCount = $totalStmt->fetchColumn();

            echo json_encode([
                'status' => 'success',
                'total' => $totalCount,
                'today' => $todayCount
            ]);
            break;

        case 'gdpr_query':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $email = trim($input['email'] ?? '');
            $visitorId = (int) ($input['visitor_id'] ?? 0);

            if (empty($email) && empty($visitorId)) {
                throw new Exception('请提供邮箱地址或访客编号');
            }

            $where = [];
            $params = [];

            if (!empty($email)) {
                $where[] = "email = :email";
                $params[':email'] = $email;
            }
            if (!empty($visitorId)) {
                $where[] = "id = :visitor_id";
                $params[':visitor_id'] = $visitorId;
            }

            $whereClause = implode(' OR ', $where);

            $visitorsStmt = $pdo->prepare("SELECT * FROM visitors WHERE ($whereClause) AND is_deleted = 0 ORDER BY created_at DESC");
            $visitorsStmt->execute($params);
            $visitors = $visitorsStmt->fetchAll();

            if (empty($visitors)) {
                echo json_encode([
                    'status' => 'success',
                    'found' => false,
                    'message' => '未找到匹配的访客数据'
                ]);
                break;
            }

            $visitorIds = array_column($visitors, 'id');
            $visitorEmails = array_unique(array_filter(array_column($visitors, 'email')));

            $consents = [];
            $exports = [];

            if (!empty($visitorIds)) {
                $idsPlaceholders = implode(',', array_fill(0, count($visitorIds), '?'));
                $consentSql = "SELECT * FROM consent_records WHERE visitor_id IN ($idsPlaceholders)";
                $consentParams = $visitorIds;

                if (!empty($visitorEmails)) {
                    $emailPlaceholders = implode(',', array_fill(0, count($visitorEmails), '?'));
                    $consentSql .= " OR email IN ($emailPlaceholders)";
                    $consentParams = array_merge($consentParams, $visitorEmails);
                }
                $consentSql .= " AND is_deleted = 0";

                $consentStmt = $pdo->prepare($consentSql);
                $consentStmt->execute($consentParams);
                $consents = $consentStmt->fetchAll();

                $exportSql = "SELECT * FROM export_history WHERE visitor_id IN ($idsPlaceholders)";
                $exportParams = $visitorIds;
                if (!empty($visitorEmails)) {
                    $emailPlaceholders = implode(',', array_fill(0, count($visitorEmails), '?'));
                    $exportSql .= " OR email IN ($emailPlaceholders)";
                    $exportParams = array_merge($exportParams, $visitorEmails);
                }
                $exportSql .= " AND is_deleted = 0";

                $exportStmt = $pdo->prepare($exportSql);
                $exportStmt->execute($exportParams);
                $exports = $exportStmt->fetchAll();
            }

            $remarks = [];
            foreach ($visitors as $v) {
                if (!empty($v['remark'])) {
                    $remarks[] = [
                        'visitor_id' => $v['id'],
                        'remark' => $v['remark'],
                        'created_at' => $v['created_at']
                    ];
                }
            }

            $reportStmt = $pdo->query("SELECT * FROM aggregated_reports ORDER BY report_date DESC LIMIT 30");
            $reports = $reportStmt->fetchAll();

            $retentionReasons = getRetentionReasons();

            echo json_encode([
                'status' => 'success',
                'found' => true,
                'data' => [
                    'visitors' => $visitors,
                    'consent_records' => $consents,
                    'export_history' => $exports,
                    'remarks' => $remarks,
                    'aggregated_reports' => count($reports)
                ],
                'retention_info' => [
                    'retainable_count' => count($reports),
                    'reasons' => $retentionReasons
                ]
            ]);
            break;

        case 'gdpr_submit':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $email = trim($input['email'] ?? '');
            $visitorId = (int) ($input['visitor_id'] ?? 0);
            $requestType = $input['request_type'] ?? 'delete';

            if (empty($email) && empty($visitorId)) {
                throw new Exception('请提供邮箱地址或访客编号');
            }
            if (!in_array($requestType, ['delete', 'anonymize'])) {
                throw new Exception('无效的请求类型');
            }

            $requestCode = generateRequestCode();

            $stmt = $pdo->prepare("INSERT INTO deletion_requests (
                request_code, email, visitor_id, request_type, status, data_scope
            ) VALUES (
                :request_code, :email, :visitor_id, :request_type, 'pending', :data_scope
            )");
            $stmt->execute([
                ':request_code' => $requestCode,
                ':email' => $email,
                ':visitor_id' => $visitorId,
                ':request_type' => $requestType,
                ':data_scope' => json_encode([
                    'include_visitors' => true,
                    'include_consents' => true,
                    'include_exports' => true,
                    'include_remarks' => true
                ])
            ]);

            $requestId = $pdo->lastInsertId();

            echo json_encode([
                'status' => 'success',
                'request_id' => $requestId,
                'request_code' => $requestCode,
                'message' => '您的数据删除请求已提交，管理员将在1-3个工作日内处理'
            ]);
            break;

        case 'gdpr_requests':
            Auth::requireLogin();

            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;
            $status = $_GET['status'] ?? '';

            $where = "WHERE 1=1";
            $params = [];

            if (!empty($status)) {
                $where .= " AND status = :status";
                $params[':status'] = $status;
            }

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM deletion_requests $where");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT * FROM deletion_requests $where ORDER BY submitted_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($params);
            $requests = $stmt->fetchAll();

            echo json_encode([
                'status' => 'success',
                'data' => $requests,
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / $limit)
            ]);
            break;

        case 'gdpr_request_detail':
            Auth::requireLogin();

            $requestId = (int) ($_GET['id'] ?? 0);
            if (!$requestId) {
                throw new Exception('请求ID不能为空');
            }

            $stmt = $pdo->prepare("SELECT * FROM deletion_requests WHERE id = :id");
            $stmt->execute([':id' => $requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception('请求不存在');
            }

            $email = $request['email'];
            $visitorId = $request['visitor_id'];

            $where = [];
            $params = [];
            if (!empty($email)) {
                $where[] = "email = :email";
                $params[':email'] = $email;
            }
            if (!empty($visitorId)) {
                $where[] = "id = :visitor_id";
                $params[':visitor_id'] = $visitorId;
            }
            $whereClause = implode(' OR ', $where);

            $visitorsStmt = $pdo->prepare("SELECT * FROM visitors WHERE ($whereClause) AND is_deleted = 0 ORDER BY created_at DESC");
            $visitorsStmt->execute($params);
            $visitors = $visitorsStmt->fetchAll();

            $visitorIds = array_column($visitors, 'id');
            $visitorEmails = array_unique(array_filter(array_column($visitors, 'email')));

            $consents = [];
            $exports = [];
            $remarks = [];

            if (!empty($visitorIds)) {
                $idsPlaceholders = implode(',', array_fill(0, count($visitorIds), '?'));
                $emailPlaceholders = !empty($visitorEmails) ? implode(',', array_fill(0, count($visitorEmails), '?')) : '';

                $consentSql = "SELECT * FROM consent_records WHERE visitor_id IN ($idsPlaceholders)";
                $consentParams = $visitorIds;
                if (!empty($emailPlaceholders)) {
                    $consentSql .= " OR email IN ($emailPlaceholders)";
                    $consentParams = array_merge($consentParams, $visitorEmails);
                }
                $consentSql .= " AND is_deleted = 0";
                $consentStmt = $pdo->prepare($consentSql);
                $consentStmt->execute($consentParams);
                $consents = $consentStmt->fetchAll();

                $exportSql = "SELECT * FROM export_history WHERE visitor_id IN ($idsPlaceholders)";
                $exportParams = $visitorIds;
                if (!empty($emailPlaceholders)) {
                    $exportSql .= " OR email IN ($emailPlaceholders)";
                    $exportParams = array_merge($exportParams, $visitorEmails);
                }
                $exportSql .= " AND is_deleted = 0";
                $exportStmt = $pdo->prepare($exportSql);
                $exportStmt->execute($exportParams);
                $exports = $exportStmt->fetchAll();

                foreach ($visitors as $v) {
                    if (!empty($v['remark'])) {
                        $remarks[] = [
                            'visitor_id' => $v['id'],
                            'remark' => $v['remark'],
                            'created_at' => $v['created_at']
                        ];
                    }
                }
            }

            $reportStmt = $pdo->query("SELECT COUNT(*) FROM aggregated_reports");
            $reportCount = $reportStmt->fetchColumn();

            $retentionReasons = getRetentionReasons();

            echo json_encode([
                'status' => 'success',
                'request' => $request,
                'related_data' => [
                    'visitors' => $visitors,
                    'consent_records' => $consents,
                    'export_history' => $exports,
                    'remarks' => $remarks,
                    'aggregated_report_count' => $reportCount
                ],
                'retention_info' => [
                    'retainable_count' => $reportCount,
                    'reasons' => $retentionReasons
                ]
            ]);
            break;

        case 'gdpr_execute':
            Auth::requireLogin();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $requestId = (int) ($input['request_id'] ?? 0);
            $actionType = $input['action'] ?? '';
            $adminNote = $input['admin_note'] ?? '';
            $scope = $input['scope'] ?? [];

            if (!$requestId) {
                throw new Exception('请求ID不能为空');
            }
            if (!in_array($actionType, ['approve_delete', 'approve_anonymize', 'reject'])) {
                throw new Exception('无效的操作类型');
            }

            $stmt = $pdo->prepare("SELECT * FROM deletion_requests WHERE id = :id");
            $stmt->execute([':id' => $requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception('请求不存在');
            }
            if ($request['status'] !== 'pending') {
                throw new Exception('该请求已处理，无法重复操作');
            }

            $pdo->beginTransaction();

            try {
                $email = $request['email'];
                $visitorId = $request['visitor_id'];

                $where = [];
                $params = [];
                if (!empty($email)) {
                    $where[] = "email = :email";
                    $params[':email'] = $email;
                }
                if (!empty($visitorId)) {
                    $where[] = "id = :visitor_id";
                    $params[':visitor_id'] = $visitorId;
                }
                $whereClause = implode(' OR ', $where);

                $deletedCount = 0;
                $anonymizedCount = 0;
                $retainedCount = 0;

                if ($actionType !== 'reject') {
                    $visitorsStmt = $pdo->prepare("SELECT * FROM visitors WHERE ($whereClause) AND is_deleted = 0");
                    $visitorsStmt->execute($params);
                    $visitors = $visitorsStmt->fetchAll();

                    $visitorIds = array_column($visitors, 'id');
                    $visitorEmails = array_unique(array_filter(array_column($visitors, 'email')));

                    if (!empty($scope['visitors'] ?? false)) {
                        if ($actionType === 'approve_delete') {
                            if (!empty($visitorIds)) {
                                $idsPlaceholders = implode(',', array_fill(0, count($visitorIds), '?'));
                                $deleteStmt = $pdo->prepare("UPDATE visitors SET is_deleted = 1, ip = NULL, user_agent = NULL, email = NULL WHERE id IN ($idsPlaceholders)");
                                $deleteStmt->execute($visitorIds);
                                $deletedCount += count($visitorIds);
                            }
                        } elseif ($actionType === 'approve_anonymize') {
                            if (!empty($visitorIds)) {
                                $idsPlaceholders = implode(',', array_fill(0, count($visitorIds), '?'));
                                $anonymizeStmt = $pdo->prepare("UPDATE visitors SET 
                                    is_anonymized = 1,
                                    ip = '[已匿名化]',
                                    email = NULL,
                                    remark = NULL,
                                    user_agent = '[已匿名化]',
                                    country = NULL,
                                    city = NULL,
                                    isp = NULL,
                                    referrer = NULL
                                WHERE id IN ($idsPlaceholders)");
                                $anonymizeStmt->execute($visitorIds);
                                $anonymizedCount += count($visitorIds);
                            }
                        }
                    }

                    if (!empty($scope['consents'] ?? false) && !empty($visitorIds)) {
                        $idsPlaceholders = implode(',', array_fill(0, count($visitorIds), '?'));
                        $consentParams = $visitorIds;
                        $consentWhere = "visitor_id IN ($idsPlaceholders)";
                        if (!empty($visitorEmails)) {
                            $emailPlaceholders = implode(',', array_fill(0, count($visitorEmails), '?'));
                            $consentWhere .= " OR email IN ($emailPlaceholders)";
                            $consentParams = array_merge($consentParams, $visitorEmails);
                        }
                        $consentDeleteStmt = $pdo->prepare("UPDATE consent_records SET is_deleted = 1, ip_address = NULL, user_agent = NULL, email = NULL WHERE ($consentWhere) AND is_deleted = 0");
                        $consentDeleteStmt->execute($consentParams);
                        $deletedCount += $consentDeleteStmt->rowCount();
                    }

                    if (!empty($scope['exports'] ?? false) && !empty($visitorIds)) {
                        $idsPlaceholders = implode(',', array_fill(0, count($visitorIds), '?'));
                        $exportParams = $visitorIds;
                        $exportWhere = "visitor_id IN ($idsPlaceholders)";
                        if (!empty($visitorEmails)) {
                            $emailPlaceholders = implode(',', array_fill(0, count($visitorEmails), '?'));
                            $exportWhere .= " OR email IN ($emailPlaceholders)";
                            $exportParams = array_merge($exportParams, $visitorEmails);
                        }
                        $exportDeleteStmt = $pdo->prepare("UPDATE export_history SET is_deleted = 1, email = NULL, file_path = NULL WHERE ($exportWhere) AND is_deleted = 0");
                        $exportDeleteStmt->execute($exportParams);
                        $deletedCount += $exportDeleteStmt->rowCount();
                    }

                    if (!empty($scope['remarks'] ?? false) && !empty($visitorIds)) {
                        $idsPlaceholders = implode(',', array_fill(0, count($visitorIds), '?'));
                        $remarkStmt = $pdo->prepare("UPDATE visitors SET remark = NULL WHERE id IN ($idsPlaceholders)");
                        $remarkStmt->execute($visitorIds);
                        $anonymizedCount += $remarkStmt->rowCount();
                    }

                    $reportStmt = $pdo->query("SELECT COUNT(*) FROM aggregated_reports");
                    $retainedCount = $reportStmt->fetchColumn();
                }

                $newStatus = $actionType === 'reject' ? 'rejected' : 'completed';
                $updateStmt = $pdo->prepare("UPDATE deletion_requests SET 
                    status = :status,
                    admin_note = :admin_note,
                    reviewed_at = :reviewed_at,
                    completed_at = :completed_at
                WHERE id = :id");
                $updateStmt->execute([
                    ':status' => $newStatus,
                    ':admin_note' => $adminNote,
                    ':reviewed_at' => date('Y-m-d H:i:s'),
                    ':completed_at' => date('Y-m-d H:i:s'),
                    ':id' => $requestId
                ]);

                $retentionReasons = getRetentionReasons();
                $receiptCode = generateReceiptCode();

                $actionText = $actionType === 'approve_delete' ? '删除' : ($actionType === 'approve_anonymize' ? '匿名化' : '拒绝');
                $receiptContent = "
个人信息处理回执单
=====================================
回执编号：{$receiptCode}
申请编号：{$request['request_code']}
申请邮箱：{$request['email']}
申请类型：" . ($request['request_type'] === 'delete' ? '删除个人数据' : '匿名化个人数据') . "
处理结果：{$actionText}
处理时间：" . date('Y-m-d H:i:s') . "

处理详情：
- 已删除记录数：{$deletedCount}
- 已匿名化记录数：{$anonymizedCount}
- 依法保留记录数：{$retainedCount}

管理员备注：{$adminNote}

保留数据说明：
{$retentionReasons['aggregated_reports']}

如有疑问，请联系网站管理员。
=====================================
                ";

                $receiptStmt = $pdo->prepare("INSERT INTO deletion_receipts (
                    request_id, receipt_code, content, deleted_count, anonymized_count, retained_count, retention_reasons
                ) VALUES (
                    :request_id, :receipt_code, :content, :deleted_count, :anonymized_count, :retained_count, :retention_reasons
                )");
                $receiptStmt->execute([
                    ':request_id' => $requestId,
                    ':receipt_code' => $receiptCode,
                    ':content' => $receiptContent,
                    ':deleted_count' => $deletedCount,
                    ':anonymized_count' => $anonymizedCount,
                    ':retained_count' => $retainedCount,
                    ':retention_reasons' => json_encode($retentionReasons)
                ]);

                $receiptId = $pdo->lastInsertId();

                $pdo->commit();

                echo json_encode([
                    'status' => 'success',
                    'receipt_id' => $receiptId,
                    'receipt_code' => $receiptCode,
                    'deleted_count' => $deletedCount,
                    'anonymized_count' => $anonymizedCount,
                    'retained_count' => $retainedCount,
                    'message' => '处理完成'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'gdpr_receipt':
            $receiptCode = $_GET['code'] ?? '';
            $requestCode = $_GET['request_code'] ?? '';

            if (empty($receiptCode) && empty($requestCode)) {
                throw new Exception('请提供回执编号或申请编号');
            }

            $where = [];
            $params = [];
            if (!empty($receiptCode)) {
                $where[] = "r.receipt_code = :receipt_code";
                $params[':receipt_code'] = $receiptCode;
            }
            if (!empty($requestCode)) {
                $where[] = "dr.request_code = :request_code";
                $params[':request_code'] = $requestCode;
            }
            $whereClause = implode(' AND ', $where);

            $stmt = $pdo->prepare("
                SELECT r.*, dr.request_code, dr.email, dr.request_type, dr.status 
                FROM deletion_receipts r 
                LEFT JOIN deletion_requests dr ON r.request_id = dr.id 
                WHERE {$whereClause}
            ");
            $stmt->execute($params);
            $receipt = $stmt->fetch();

            if (!$receipt) {
                throw new Exception('回执不存在');
            }

            $format = $_GET['format'] ?? 'json';

            if ($format === 'download') {
                header('Content-Type: text/plain; charset=utf-8');
                header('Content-Disposition: attachment; filename="receipt_' . $receipt['receipt_code'] . '.txt"');
                echo $receipt['content'];
                exit;
            }

            echo json_encode([
                'status' => 'success',
                'receipt' => $receipt
            ]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => '未知操作']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
