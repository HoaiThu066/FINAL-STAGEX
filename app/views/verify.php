<div class="row justify-content-center">
    <div class="col-md-6">
        <?php if (!empty($error)): ?>
            <!-- Display OTP error above the heading -->
            <div class="alert alert-danger mb-2">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <h2 class="h4 mb-3">Xác minh tài khoản</h2>
        <p>Một mã xác thực đã được gửi tới địa chỉ email của bạn. Vui lòng nhập mã bên dưới để hoàn tất đăng ký/đăng nhập.</p>
        <!-- Countdown timer shows remaining validity of OTP -->
        <?php if (isset($remaining) && $remaining > 0): ?>
            <p class="small text-warning">Mã OTP sẽ hết hạn trong: <span id="otp-timer"></span></p>
        <?php endif; ?>
        <div id="otp-form-wrapper">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Mã xác thực (OTP)</label>
                    <input type="text" name="otp" class="form-control" required maxlength="6" pattern="[0-9]{6}" placeholder="Nhập 6 chữ số">
                </div>
                <!-- Error output moved above the heading -->
                <button type="submit" class="btn btn-warning w-100">Xác minh</button>
            </form>
        </div>
        <!-- Expired message shown when OTP timer hits zero -->
        <div id="otp-expired-message" class="alert alert-danger d-none">
            Mã OTP đã hết hạn. <a href="<?= BASE_URL ?>index.php" class="btn btn-sm btn-light ms-2">Quay về</a>
        </div>
        <script>
        // Only run countdown if a remaining value was passed from the controller
        (function() {
            var remaining = <?= isset($remaining) ? intval($remaining) : 0 ?>;
            if (remaining > 0) {
                var timerEl = document.getElementById('otp-timer');
                var formWrapper = document.getElementById('otp-form-wrapper');
                var expiredEl = document.getElementById('otp-expired-message');
                function updateTimer() {
                    if (remaining <= 0) {
                        // Hide form and show expired message
                        if (formWrapper) formWrapper.classList.add('d-none');
                        if (expiredEl) expiredEl.classList.remove('d-none');
                        return;
                    }
                    var mins = Math.floor(remaining / 60);
                    var secs = remaining % 60;
                    if (timerEl) timerEl.textContent = mins.toString().padStart(2, '0') + ':' + secs.toString().padStart(2, '0');
                    remaining--;
                    setTimeout(updateTimer, 1000);
                }
                updateTimer();
            }
        })();
        </script>
    </div>
</div>