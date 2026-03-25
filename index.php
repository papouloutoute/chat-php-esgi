<?php
session_start();

$db_path = '/var/www/html/data/chat.db';
$pdo = new PDO('sqlite:' . $db_path);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set_username'])) {
        $username = trim(htmlspecialchars($_POST['username']));
        if (strlen($username) >= 2 && strlen($username) <= 20) {
            $_SESSION['username'] = $username;
        } else {
            $error = 'Le pseudo doit contenir entre 2 et 20 caractères.';
        }
    }

    if (isset($_POST['send_message']) && isset($_SESSION['username'])) {
        $message = trim(htmlspecialchars($_POST['message']));
        if (strlen($message) > 0 && strlen($message) <= 500) {
            $stmt = $pdo->prepare("INSERT INTO messages (username, message) VALUES (?, ?)");
            $stmt->execute([$_SESSION['username'], $message]);
        }
        header('Location: /');
        exit;
    }

    if (isset($_POST['logout'])) {
        session_destroy();
        header('Location: /');
        exit;
    }
}

$stmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 50");
$messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat ESGI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <?php if (isset($_SESSION['username'])): ?>
    <meta http-equiv="refresh" content="10">
    <?php endif; ?>
</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 700px;">
    <h2 class="mb-4">Chat ESGI</h2>

    <?php if (!isset($_SESSION['username'])): ?>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Rejoindre le chat</h5>
            <form method="POST">
                <div class="mb-3">
                    <input type="text" name="username" class="form-control" placeholder="Votre pseudo" maxlength="20" required autofocus>
                </div>
                <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= $error ?></div>
                <?php endif; ?>
                <button type="submit" name="set_username" class="btn btn-primary">Rejoindre</button>
            </form>
        </div>
    </div>

    <?php else: ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Connecté : <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
            <form method="POST">
                <button type="submit" name="logout" class="btn btn-sm btn-outline-danger">Quitter</button>
            </form>
        </div>
        <div class="card-body p-0">
            <div id="messages" style="height: 400px; overflow-y: auto; padding: 1rem;">
                <?php foreach ($messages as $msg): ?>
                <div class="mb-2">
                    <span class="fw-semibold"><?= htmlspecialchars($msg['username']) ?></span>
                    <small class="text-muted ms-1"><?= $msg['created_at'] ?></small>
                    <div><?= htmlspecialchars($msg['message']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-footer">
            <form method="POST" class="d-flex gap-2">
                <input type="text" name="message" class="form-control" placeholder="Votre message..." maxlength="500" required autofocus>
                <button type="submit" name="send_message" class="btn btn-primary">Envoyer</button>
            </form>
        </div>
    </div>

    <script>
        const msgs = document.getElementById('messages');
        msgs.scrollTop = msgs.scrollHeight;
    </script>
    <?php endif; ?>
</div>
</body>
</html>
