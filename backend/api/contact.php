<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// ✅ プリフライト(OPTIONS)なら処理終了
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=portfolio;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // JSON取得
    $data = json_decode(file_get_contents('php://input'), true);

    $name = $data["name"] ?? "";
    $furigana = $data["furigana"] ?? "";
    $gender = $data["gender"] ?? "";
    $email = $data["email"] ?? "";
    $tel = $data["tel"] ?? "";
    $inquiryType = $data["inquiryType"] ?? "";
    $topicsArray = $data["topics"] ?? [];
    $message = $data["message"] ?? "";

    // 配列 → 文字列 ("HP作成, LP作成" のような形式)
    $topics = implode(", ", $topicsArray);

    // ----------------------------------
    // ✅ ID 採番（トランザクション + FOR UPDATE）
    // ----------------------------------
    $pdo->beginTransaction();

    $stmt = $pdo->query("SELECT id FROM contacts ORDER BY id DESC LIMIT 1 FOR UPDATE");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $newId = "00000001";
    } else {
        $currentIdNum = intval($row["id"]);
        $newIdNum = $currentIdNum + 1;
        $newId = str_pad((string)$newIdNum, 8, "0", STR_PAD_LEFT);
    }

    // ----------------------------------
    // ✅ INSERT
    // ----------------------------------
    $sql = "INSERT INTO contacts 
            (id, name, furigana, gender, email, tel, inquiryType, topics, message, created_at)
            VALUES 
            (:id, :name, :furigana, :gender, :email, :tel, :inquiryType, :topics, :message, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":id" => $newId,
        ":name" => $name,
        ":furigana" => $furigana,
        ":gender" => $gender,
        ":email" => $email,
        ":tel" => $tel,
        ":inquiryType" => $inquiryType,
        ":topics" => $topics,
        ":message" => $message,
    ]);

    $pdo->commit();

    echo json_encode(["status" => "success", "message" => "送信が完了しました。"]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    exit;
}
