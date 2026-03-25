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
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #1a1a2e;
            color: #eee;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 700px;
            padding: 20px;
        }

        h1 {
            text-align: right;
            margin-bottom: 20px;
            color: #4fc3f7;
            font-size: 1.6rem;
        }

        .login-box {
            background: #16213e;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
        }

        .login-box input[type=text] {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #4fc3f7;
            background: #0f3460;
            color: #fff;
            font-size: 1rem;
            margin-bottom: 12px;
        }

        .chat-box {
            background: #16213e;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            height: 75vh;
        }

        .chat-header {
            padding: 14px 20px;
            background: #0f3460;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header span { color: #4fc3f7; font-weight: bold; }

        .messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .message {
            background: #0f3460;
            border-radius: 10px;
            padding: 10px 14px;
            max-width: 80%;
        }

        .message.own {
            align-self: flex-end;
            background: #1565c0;
        }

        .message .author {
            font-size: 0.75rem;
            color: #4fc3f7;
            margin-bottom: 4px;
        }

        .message .text { font-size: 0.95rem; }

        .message .time {
            font-size: 0.7rem;
            color: #888;
            margin-top: 4px;
            text-align: right;
        }

        .chat-input {
            padding: 14px;
            display: flex;
            gap: 10px;
            border-top: 1px solid #0f3460;
        }

        .chat-input input[type=text] {
            flex: 1;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #4fc3f7;
            background: #0f3460;
            color: #fff;
            font-size: 0.95rem;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            background: #4fc3f7;
            color: #1a1a2e;
            font-weight: bold;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.2s;
        }

        button:hover { background: #81d4fa; }

        button.danger {
            background: #ef5350;
            color: #fff;
            font-size: 0.8rem;
            padding: 8px 14px;
        }

        .error { color: #ef5350; margin-top: 10px; font-size: 0.9rem; }
    </style>
    <?php if (isset($_SESSION['username'])): ?>
    <meta http-equiv="refresh" content="10">
    <?php endif; ?>
</head>
<body>
<div class="container">
    <h1>💬 Chat ESGI</h1>

    <?php if (!isset($_SESSION['username'])): ?>
    <div class="login-box">
        <p style="margin-bottom:16px;color:#aaa;">Choisissez un pseudo pour rejoindre le chat</p>
        <form method="POST">
            <input type="text" name="username" placeholder="Votre pseudoTESTCI" maxlength="20" required autofocus>
            <button type="submit" name="set_username">Rejoindre le chat</button>
            <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
        </form>
    </div>

    <?php else: ?>
    <div class="chat-box">
        <div class="chat-header">
            <span>Connecté en tant que : <?= htmlspecialchars($_SESSION['username']) ?></span>
            <form method="POST" style="display:inline">
                <button type="submit" name="logout" class="danger">Quitter</button>
            </form>
        </div>

        <div class="messages" id="messages">
            <?php foreach ($messages as $msg): ?>
            <div class="message <?= $msg['username'] === $_SESSION['username'] ? 'own' : '' ?>">
                <div class="author"><?= htmlspecialchars($msg['username']) ?></div>
                <div class="text"><?= htmlspecialchars($msg['message']) ?></div>
                <div class="time"><?= $msg['created_at'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <form method="POST" class="chat-input">
            <input type="text" name="message" placeholder="Votre message..." maxlength="500" required autofocus>
            <button type="submit" name="send_message">Envoyer</button>
        </form>
    </div>

    <script>
        const msgs = document.getElementById('messages');
        msgs.scrollTop = msgs.scrollHeight;
    </script>
    <?php endif; ?>
</div>
</body>
</html>
