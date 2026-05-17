<?php use App\Core\Csrf; ?>

<section class="login-panel">
    <aside class="login-visual" aria-label="PolicyPilot renewal intelligence preview">
        <div class="visual-grid" aria-hidden="true"></div>
        <div class="visual-orbit" aria-hidden="true">
            <span></span>
            <span></span>
        </div>
    </aside>

    <form class="auth-card login-form" method="post" action="?action=login" data-validate>
        <?= Csrf::field() ?>
        <div class="login-brand">
            <span class="brand-mark login-brand-mark">P</span>
            <span>Policy<span>Pilot</span></span>
        </div>
        <label>
            <span>Email address</span>
            <input type="email" name="email" value="admin@zimnat.test" required autocomplete="email" data-login-email>
        </label>
        <label>
            <span>Password</span>
            <input type="password" name="password" value="password" required autocomplete="current-password" data-login-password>
        </label>
        <button class="button primary full" type="submit">Login</button>

        <div class="demo-credentials" aria-label="Demo credentials">
            <span>Demo access</span>
            <div>
                <button type="button" data-demo-login data-email="admin@zimnat.test">Admin</button>
                <button type="button" data-demo-login data-email="officer@zimnat.test">Policy Officer</button>
                <button type="button" data-demo-login data-email="viewer@zimnat.test">Viewer</button>
            </div>
            <small>Password for all demo users: password</small>
        </div>
    </form>
</section>
