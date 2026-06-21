<?php
require_once __DIR__ . '/auth.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $message = '请输入用户名和密码';
            $messageType = 'error';
        } else {
            $result = Auth::login($username, $password);
            if ($result['success']) {
                header('Location: admin.php');
                exit;
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        }
    } elseif ($action === 'logout') {
        Auth::logout();
        header('Location: login.php');
        exit;
    }
}

if (Auth::check()) {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - 探针管理后台</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900 min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-r from-blue-500 to-indigo-600 shadow-lg shadow-blue-500/25 mb-4">
                <i class="ri-shield-keyhole-line text-3xl text-white"></i>
            </div>
            <h1 class="text-2xl font-bold text-white mb-2">管理员登录</h1>
            <p class="text-slate-400 text-sm">请输入您的账号密码以访问管理后台</p>
        </div>

        <div class="bg-white/95 backdrop-blur rounded-2xl shadow-2xl p-8 border border-white/20">
            <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 rounded-xl <?php echo $messageType === 'error' ? 'bg-red-50 border border-red-200' : 'bg-green-50 border border-green-200'; ?>">
                    <div class="flex items-center">
                        <i class="<?php echo $messageType === 'error' ? 'ri-error-warning-line text-red-500' : 'ri-checkbox-circle-line text-green-500'; ?> text-xl mr-2"></i>
                        <span class="<?php echo $messageType === 'error' ? 'text-red-700' : 'text-green-700'; ?> text-sm font-medium"><?php echo htmlspecialchars($message); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <input type="hidden" name="action" value="login">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        <i class="ri-user-line mr-1 text-slate-400"></i>用户名
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            name="username"
                            required
                            autocomplete="username"
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                            class="w-full px-4 py-3 pl-11 rounded-xl border-2 border-slate-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all text-slate-800"
                            placeholder="请输入用户名"
                        >
                        <i class="ri-user-3-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        <i class="ri-lock-line mr-1 text-slate-400"></i>密码
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            required
                            autocomplete="current-password"
                            class="w-full px-4 py-3 pl-11 pr-11 rounded-xl border-2 border-slate-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition-all text-slate-800"
                            placeholder="请输入密码"
                        >
                        <i class="ri-lock-password-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <button
                            type="button"
                            onclick="togglePassword()"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition"
                            title="显示/隐藏密码"
                        >
                            <i id="toggleIcon" class="ri-eye-line text-lg"></i>
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full py-3.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-semibold hover:shadow-lg hover:shadow-blue-500/30 active:scale-[0.98] transition-all"
                >
                    <i class="ri-login-box-line mr-2"></i>登录系统
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-100">
                <div class="flex items-start p-3 rounded-xl bg-amber-50 border border-amber-200">
                    <i class="ri-information-line text-amber-500 text-lg mr-3 flex-shrink-0 mt-0.5"></i>
                    <div class="text-xs text-amber-800 space-y-1">
                        <p><strong>默认登录信息（首次使用）：</strong></p>
                        <p>用户名：<code class="bg-amber-100 px-1.5 py-0.5 rounded">admin</code></p>
                        <p>密　码：<code class="bg-amber-100 px-1.5 py-0.5 rounded">admin123</code></p>
                        <p class="pt-1 text-amber-700">* 登录后请立即修改默认密码</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="index.php" class="inline-flex items-center text-sm text-slate-400 hover:text-white transition">
                <i class="ri-arrow-left-line mr-2"></i>
                返回首页
            </a>
        </div>

        <div class="mt-4 text-center text-xs text-slate-500">
            <p>© 2024 站点分析台 · 所有连接均受安全加密保护</p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.className = 'ri-eye-off-line text-lg';
            } else {
                pwd.type = 'password';
                icon.className = 'ri-eye-line text-lg';
            }
        }
    </script>
</body>
</html>
