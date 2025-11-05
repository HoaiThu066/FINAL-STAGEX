<div class="row mb-4">
    <div class="col-md-4">
        <img src="<?= htmlspecialchars($show['poster_image_url']) ?>" alt="<?= htmlspecialchars($show['title']) ?>" class="img-fluid rounded-3">
    </div>
    <div class="col-md-8">
        <h2 class="mb-2">
            <!-- Average rating above the show title -->
            <?php if (isset($avgRating) && $avgRating !== null): ?>
                <div class="mb-1">
                    <?php
                    $ratingVal = (float)$avgRating;
                    $fullStars = floor($ratingVal);
                    $halfStar  = ($ratingVal - $fullStars) >= 0.5;
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $fullStars) {
                            echo '<i class="bi bi-star-fill text-warning"></i>';
                        } elseif ($halfStar && $i === $fullStars + 1) {
                            echo '<i class="bi bi-star-half text-warning"></i>';
                        } else {
                            echo '<i class="bi bi-star text-warning"></i>';
                        }
                    }
                    ?>
                    <span class="text-light ms-1"> <?= number_format($avgRating, 1) ?>/5</span>
                </div>
            <?php else: ?>
                <div class="mb-1"><span class="text-muted">Chưa có đánh giá</span></div>
            <?php endif; ?>
            <?= htmlspecialchars($show['title']) ?>
        </h2>
        <p><strong>Thể loại:</strong> <?= htmlspecialchars($show['genres']) ?></p>
        <p><strong>Đạo diễn:</strong> <?= htmlspecialchars($show['director'] ?? 'N/A') ?></p>
        <p><strong>Thời lượng:</strong> <?= htmlspecialchars($show['duration_minutes'] ?? '') ?> phút</p>
        <p><?= nl2br(htmlspecialchars($show['description'])) ?></p>
    </div>
</div>


<h3 class="h5 mb-3">Lịch suất diễn</h3>
<?php if ($performances): ?>
    <div class="table-responsive">
        <table class="table table-dark table-striped">
            <thead>
                <tr>
                    <th>Ngày</th>
                    <th>Giờ</th>
                    <th>Phòng</th>
                    <th>Giá chỉ từ (VND)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($performances as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($p['performance_date']))) ?></td>
                        <td><?= htmlspecialchars(substr($p['start_time'], 0, 5)) ?></td>
                        <td><?= htmlspecialchars($p['theater_name']) ?></td>
                        <td><?= isset($p['lowest_price']) ? number_format($p['lowest_price'], 0, ',', '.') : number_format($p['price'], 0, ',', '.') ?></td>
                        <td>
                            <?php
                            // Lock the booking button if the performance date has passed.  Comparing
                            // against the current date (Y-m-d) ensures that shows in the past
                            // cannot be selected.  When disabled, a secondary colour is used and
                            // the button is rendered inactive.
                            $isPast = strtotime($p['performance_date']) < strtotime(date('Y-m-d'));
                            ?>
                            <?php if ($isPast): ?>
                                <button class="btn btn-secondary btn-sm" disabled>Chọn suất</button>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>index.php?pg=select&performance_id=<?= $p['performance_id'] ?>" class="btn btn-warning btn-sm">Chọn suất</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p>Không có suất diễn nào.</p>
<?php endif; ?>


<!-- Review submission form -->
<div class="mt-5">
    <h3 class="h5 mb-3">Đánh giá của bạn</h3>
    <?php $currentUser = $_SESSION['user'] ?? null; ?>
    <?php if ($currentUser && isset($currentUser['user_type']) && $currentUser['user_type'] === 'customer'): ?>
        <form method="post">
            <!-- Star rating input -->
            <div class="mb-3">
                <label class="form-label d-block">Đánh giá:</label>
                <style>
                .star-rating {
                    direction: rtl;
                    display: inline-flex;
                    font-size: 1.5rem;
                }
                .star-rating input[type=radio] {
                    display: none;
                }
                .star-rating label {
                    color: #666;
                    cursor: pointer;
                }
                .star-rating input[type=radio]:checked ~ label,
                .star-rating label:hover,
                .star-rating label:hover ~ label {
                    color: #ffc107;
                }
                </style>
                <div class="star-rating">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>">
                        <label for="star<?= $i ?>"><i class="bi bi-star-fill"></i></label>
                    <?php endfor; ?>
                </div>
            </div>
            <!-- Review content -->
            <div class="mb-3">
                <label for="reviewContent" class="form-label">Nội dung</label>
                <textarea id="reviewContent" name="content" class="form-control" rows="3" placeholder="Viết cảm nhận của bạn..."></textarea>
            </div>
            <button type="submit" class="btn btn-warning">Gửi đánh giá</button>
        </form>
    <?php else: ?>
        <p>Bạn cần <a href="<?= BASE_URL ?>index.php?pg=login">đăng nhập</a> với vai trò khách hàng để gửi đánh giá.</p>
    <?php endif; ?>
</div>


<!-- Existing reviews -->
<div class="mt-5">
    <h3 class="h5 mb-3">Đánh giá</h3>
    <?php if (!empty($reviews)): ?>
        <?php foreach ($reviews as $rev): ?>
            <div class="mb-4 pb-3 border-bottom border-secondary">
                <div class="d-flex align-items-center mb-1">
                    <div class="fw-bold me-2"><?= htmlspecialchars($rev['account_name'] ?? $rev['name'] ?? 'Ẩn danh') ?></div>
                    <div class="me-2">
                        <?php
                        $rating = (int)($rev['rating'] ?? 0);
                        for ($j = 1; $j <= 5; $j++) {
                            if ($j <= $rating) {
                                echo '<i class="bi bi-star-fill text-warning"></i>';
                            } else {
                                echo '<i class="bi bi-star text-warning"></i>';
                            }
                        }
                        ?>
                    </div>
                    <!-- Display the review creation date clearly on dark backgrounds. -->
                    <small class="ms-auto" style="color:#adb5bd; font-size:0.85rem;">
                        <?= date('d/m/Y', strtotime($rev['created_at'])) ?>
                    </small>
                </div>
                <p class="mb-0">
                    <?php
                    // Wrap review content at 15 words per line.  This preserves the full
                    // content while ensuring lines do not stretch unbroken across the page.
                    $text = $rev['content'];
                    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
                    $chunks = array_chunk($words, 15);
                    $lines = array_map(function ($chunk) {
                        return implode(' ', $chunk);
                    }, $chunks);
                    $wrapped = implode("\n", $lines);
                    echo nl2br(htmlspecialchars($wrapped));
                    ?>
                </p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Chưa có đánh giá nào cho vở diễn này.</p>
    <?php endif; ?>
</div>

