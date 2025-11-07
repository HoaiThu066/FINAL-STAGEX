<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card bg-secondary text-light mt-4">
            <div class="card-body p-4">
                <?php if ($stage === 'request'): ?>
                    <h2 class="h5 mb-3 text-center">Quên mật khẩu</h2>
                    <p class="small text-muted">Nhập email của bạn để nhận mã xác thực.</p>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger py-2">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($info)): ?>
                        <div class="alert alert-success py-2">
                            <?= htmlspecialchars($info) ?>
                        </div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">Gửi mã xác thực</button>
                    </form>
                <?php elseif ($stage === 'otp'): ?>
                    <h2 class="h5 mb-3 text-center">Nhập mã xác thực</h2>
                    <p class="small text-muted">Một mã xác thực đã được gửi tới email của bạn. Vui lòng nhập mã để tiếp tục.</p>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger py-2">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($info)): ?>
                        <div class="alert alert-success py-2">
                            <?= htmlspecialchars($info) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($remaining) && $remaining > 0): ?>
                        <p class="small text-warning">Mã OTP sẽ hết hạn trong: <span id="otp-timer"></span></p>
                    <?php endif; ?>
                    <div id="otp-form-wrapper">
                        <form method="post">
                            <div class="mb-3">
                                <label for="otp" class="form-label">Mã xác thực</label>
                                <input type="text" class="form-control" id="otp" name="otp" required>
                            </div>
                            <button type="submit" class="btn btn-warning w-100">Xác nhận</button>
                        </form>
                    </div>
                    <div id="otp-expired-message" class="alert alert-danger d-none">
                        Mã OTP đã hết hạn. <a href="<?= BASE_URL ?>index.php" class="btn btn-sm btn-light ms-2">Quay về</a>
                    </div>
                    <script>
                    (function(){
                        var remaining = <?= isset($remaining) ? intval($remaining) : 0 ?>;
                        if (remaining > 0) {
                            var timerEl = document.getElementById('otp-timer');
                            var formWrapper = document.getElementById('otp-form-wrapper');
                            var expiredEl = document.getElementById('otp-expired-message');
                            function updateTimer() {
                                if (remaining <= 0) {
                                    if (formWrapper) formWrapper.classList.add('d-none');
                                    if (expiredEl) expiredEl.classList.remove('d-none');
                                    return;
                                }
                                var mins = Math.floor(remaining / 60);
                                var secs = remaining % 60;
                                if (timerEl) timerEl.textContent = mins.toString().padStart(2,'0') + ':' + secs.toString().padStart(2,'0');
                                remaining--;
                                setTimeout(updateTimer, 1000);
                            }
                            updateTimer();
                        }
                    })();
                    </script>
                <?php elseif ($stage === 'reset'): ?>
                    <h2 class="h5 mb-3 text-center">Đặt lại mật khẩu</h2>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger py-2">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($info)): ?>
                        <div class="alert alert-success py-2">
                            <?= htmlspecialchars($info) ?>
                        </div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3 position-relative">
                            <label for="password" class="form-label">Mật khẩu mới</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control" required style="border-right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                                <span class="input-group-text bg-white" style="cursor: pointer; border-left: 0; border-top-left-radius: 0; border-bottom-left-radius: 0;" onclick="togglePw('password', this)">
                                    <i class="bi bi-eye-slash"></i>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required style="border-right: 0; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                                <span class="input-group-text bg-white" style="cursor: pointer; border-left: 0; border-top-left-radius: 0; border-bottom-left-radius: 0;" onclick="togglePw('confirm_password', this)">
                                    <i class="bi bi-eye-slash"></i>
                                </span>
                            </div>
                         </div>
                        <button type="submit" class="btn btn-warning w-100">Cập nhật mật khẩu</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function togglePw(inputId, iconEl) {
    var input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        iconEl.querySelector('i').classList.remove('bi-eye-slash');
        iconEl.querySelector('i').classList.add('bi-eye');
    } else {
        input.type = 'password';
        iconEl.querySelector('i').classList.remove('bi-eye');
        iconEl.querySelector('i').classList.add('bi-eye-slash');
    }
}
</script>