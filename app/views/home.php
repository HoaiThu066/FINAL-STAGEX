<?php

?>

<?php if (!empty($heroShows)): ?>
    <div id="homeCarousel" class="carousel slide mb-5" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <?php foreach ($heroShows as $index => $_hs): ?>
                <button type="button" data-bs-target="#homeCarousel" data-bs-slide-to="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>" aria-current="<?= $index === 0 ? 'true' : 'false' ?>" aria-label="Slide <?= $index + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
        <div class="carousel-inner">
            <?php foreach ($heroShows as $index => $hs): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <img src="<?= htmlspecialchars($hs['poster_image_url']) ?>" class="d-block w-100" alt="<?= htmlspecialchars($hs['title']) ?>" style="height:400px; object-fit:cover;">
                    <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded-3 p-4">
                        <h3><?= htmlspecialchars($hs['title']) ?></h3>
                        <p>Khám phá và đặt vé ngay hôm nay.</p>
                        <a href="<?= BASE_URL ?>index.php?pg=show&id=<?= $hs['show_id'] ?>" class="btn btn-warning">Chi tiết</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($heroShows) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#homeCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#homeCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        <?php endif; ?>
    </div>
<?php endif; ?>

<section class="mb-5">
    <h2 class="h4 mb-3">Sắp ra mắt</h2>
    <?php if (!empty($upcomingShows)): ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($upcomingShows as $show): ?>
                <div class="col">
                    <div class="card h-100">
                        <img src="<?= htmlspecialchars($show['poster_image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($show['title']) ?>" style="height:200px; object-fit:cover;">
                        <div class="card-body d-flex flex-column">
                            <div class="mb-1">
                                <?php if (isset($show['avg_rating']) && $show['avg_rating'] !== null): ?>
                                    <?php
                                    $ratingVal = (float)$show['avg_rating'];
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
                                    <span class="text-light ms-1">
                                        <?= number_format($show['avg_rating'], 1) ?>/5
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">Chưa có đánh giá</span>
                                <?php endif; ?>
                            </div>
                            <h5 class="card-title mb-2">
                                <?= htmlspecialchars($show['title']) ?>
                            </h5>
                            <?php if (!empty($show['nearest_date'])): ?>
                                <p class="small flex-fill">Suất gần nhất: <?= date('d/m/Y', strtotime($show['nearest_date'])) ?></p>
                            <?php else: ?>
                                <p class="small flex-fill">Chưa có suất diễn</p>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>index.php?pg=show&id=<?= $show['show_id'] ?>" class="btn btn-outline-warning mt-auto">Chi tiết</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>Không có vở diễn sắp ra mắt.</p>
    <?php endif; ?>
</section>

<section class="mb-5">
    <h2 class="h4 mb-3">Đang mở bán</h2>
    <?php if (!empty($sellingShows)): ?>
        <?php
        $chunks = array_chunk($sellingShows, 4);
        ?>
        <div id="sellingCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php foreach ($chunks as $idx => $chunk): ?>
                    <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
                            <?php foreach ($chunk as $show): ?>
                                <div class="col">
                                    <div class="card h-100">
                                        <img src="<?= htmlspecialchars($show['poster_image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($show['title']) ?>" style="height:180px; object-fit:cover;">
                                        <div class="card-body d-flex flex-column">
                                         
                                            <div class="mb-1">
                                                <?php if (isset($show['avg_rating']) && $show['avg_rating'] !== null): ?>
                                                    <?php
                                                    $ratingVal = (float)$show['avg_rating'];
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
                                                    <span class="text-light ms-1">
                                                        <?= number_format($show['avg_rating'], 1) ?>/5
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Chưa có đánh giá</span>
                                                <?php endif; ?>
                                            </div>
                                            <h6 class="card-title mb-2"><?= htmlspecialchars($show['title']) ?></h6>
                                            <?php if (!empty($show['nearest_date'])): ?>
                                                <p class="small flex-fill">Suất gần nhất: <?= date('d/m/Y', strtotime($show['nearest_date'])) ?></p>
                                            <?php else: ?>
                                                <p class="small flex-fill">Chưa có suất diễn</p>
                                            <?php endif; ?>
                                            <a href="<?= BASE_URL ?>index.php?pg=show&id=<?= $show['show_id'] ?>" class="btn btn-outline-warning mt-auto btn-sm">Chi tiết</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($chunks) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#sellingCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#sellingCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p>Không có vở diễn đang mở bán.</p>
    <?php endif; ?>
</section>

<section class="mb-5">
    <h2 class="h4 mb-3">Đánh giá gần nhất</h2>
    <?php if (!empty($latestReviews)): ?>
        <?php

        $limitedReviews = array_slice($latestReviews, 0, 15);
        $reviewChunks = array_chunk($limitedReviews, 5);
        ?>
        <div id="reviewCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php foreach ($reviewChunks as $index => $chunk): ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-4">
                            <?php foreach ($chunk as $rv): ?>
                                <div class="col">
                                    
                                    <div class="card h-100 bg-dark text-light border border-secondary">
                                        <div class="card-body d-flex flex-column">
                                            <h6 class="card-title text-truncate" title="<?= htmlspecialchars($rv['title']) ?>">
                                                <?= htmlspecialchars($rv['title']) ?>
                                            </h6>
                                            <div class="mb-2">
                                                <?php
                                                $rating = (int)($rv['rating'] ?? 0);
                                                for ($star = 1; $star <= 5; $star++) {
                                                    if ($star <= $rating) {
                                                        echo '<i class="bi bi-star-fill text-warning"></i>';
                                                    } else {
                                                        echo '<i class="bi bi-star text-warning"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <small class="text-muted mb-2">Bởi <?= htmlspecialchars($rv['account_name'] ?? '') ?> - <?= date('d/m/Y', strtotime($rv['created_at'])) ?></small>
                                            <p class="card-text flex-fill" style="white-space: pre-wrap;">
                                                <?php
                                               
                                                $words = preg_split('/\s+/', $rv['content'], -1, PREG_SPLIT_NO_EMPTY);
                                                $maxWords = 100;
                                                if (count($words) > $maxWords) {
                                                    $words = array_slice($words, 0, $maxWords);
                                                    $words[] = '...';
                                                }
                                                $chunks = array_chunk($words, 15);
                                                $lines = array_map(function ($chunk) {
                                                    return implode(' ', $chunk);
                                                }, $chunks);
                                                $wrapped = implode("\n", $lines);
                                                echo nl2br(htmlspecialchars($wrapped));
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($reviewChunks) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#reviewCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#reviewCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p class="text-muted">Hiện chưa có đánh giá nào. Hãy trở thành người đầu tiên để lại cảm nhận sau khi xem vở diễn!</p>
    <?php endif; ?>
</section>

<section class="mb-5">
    <h2 class="h4 mb-4">Về chúng tôi</h2>
    <div class="row align-items-center g-4">
        <div class="col-md-6">
            <p>StageX là không gian hội tụ của những trái tim yêu nghệ thuật sân khấu. Chúng tôi mang đến những vở diễn đa dạng, từ kinh điển đến hiện đại, được tuyển chọn kỹ lưỡng để phục vụ khán giả. Tại đây, bạn sẽ tìm thấy sự kết hợp giữa chất lượng nghệ thuật và trải nghiệm đặt vé trực tuyến tiện lợi.</p>
            <a href="<?= BASE_URL ?>index.php?pg=about" class="btn btn-warning mt-3">Tìm hiểu thêm</a>
        </div>
        <div class="col-md-6 text-center">
                 <img src="<?= BASE_URL ?>assets/images/banner-1.png" alt="StageX" class="img-fluid rounded-3" style="max-height:250px; object-fit:cover;">
        </div>
    </div>
</section>

