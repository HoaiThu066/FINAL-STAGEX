<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card bg-secondary text-light mt-4">
            <div class="card-body p-4">
                <?php if (empty($_SESSION['otp_sent'])): ?>
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

                <?php else: ?>
                    <h2 class="h5 mb-3 text-center">Nhập mã xác thực</h2>
                    <p class="small text-muted">Mã xác thực đã được gửi tới email của bạn. Vui lòng nhập mã để tiếp tục.</p>

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
                        Mã OTP đã hết hạn. 
                        <a href="<?= BASE_URL ?>forgot.php" class="btn btn-sm btn-light ms-2">Gửi lại</a>
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
