<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

// プリフライト(OPTIONS)リクエストは終了
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // -----------------------------
    // DB接続
    // -----------------------------
    $pdo = new PDO("mysql:host=localhost;dbname=portfolio;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // -----------------------------
    // JSONデータ取得
    // -----------------------------
    $data = json_decode(file_get_contents('php://input'), true);

    $name = $data["name"] ?? "";
    $furigana = $data["furigana"] ?? "";
    $gender = $data["gender"] ?? "";
    $email = $data["email"] ?? "";
    $tel = $data["tel"] ?? "";
    $inquiryType = $data["inquiryType"] ?? "";
    $topicsArray = $data["topics"] ?? [];
    $message = $data["message"] ?? "";

    $topics = implode(", ", $topicsArray);

    // -----------------------------
    // ID採番（トランザクション + FOR UPDATE）
    // -----------------------------
    $pdo->beginTransaction();

    $stmt = $pdo->query("SELECT id FROM contacts ORDER BY id DESC LIMIT 1 FOR UPDATE");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $newId = "00000001";
    } else {
        $newId = str_pad((string)(intval($row["id"]) + 1), 8, "0", STR_PAD_LEFT);
    }

    // -----------------------------
    // DB INSERT
    // -----------------------------
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

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => "DB登録エラー: " . $e->getMessage()]);
    exit;
}

// -----------------------------
// メール送信
// -----------------------------
try {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 0; // 0=出力なし、2=デバッグ
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'rinka.mrng1104@gmail.com';
    $mail->Password = 'nrjk myga ekdo mkzh';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('rinka.mrng1104@gmail.com', 'お問い合わせ通知');
    $mail->addAddress('rinka.mrng1104@example.com');

    $mail->Subject = "お問い合わせが届きました - {$name} 様";
    $mail->Body = <<<EOT
氏名: {$name}
フリガナ: {$furigana}
性別: {$gender}
メール: {$email}
電話番号: {$tel}
お問い合わせ種別: {$inquiryType}
相談対象: {$topics}
メッセージ:
{$message}
EOT;

    $mail->send();
} catch (Exception $e) {
    // 送信失敗はログに残すだけ
    error_log("メール送信失敗: " . $mail->ErrorInfo);
}

// -----------------------------
// 成功レスポンス
// -----------------------------
echo json_encode(["status" => "success", "message" => "送信が完了しました。"]);
exit;
