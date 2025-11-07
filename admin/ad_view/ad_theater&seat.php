<?php /** @var array $theaters */ ?>
<?php /** @var array $categories */ ?>
<h2 class="h4 mb-4">Quản lý rạp &amp; ghế</h2>

<div class="row">
    
    <div class="col-12 col-md-4">
       
        <section class="mb-4">
            <h3 class="h5">Tạo rạp</h3>
            <form method="post" class="row g-3">
                <input type="hidden" name="type" value="theater_create">
                <div class="col-12">
                    <input type="text" name="theater_name" class="form-control" placeholder="Tên rạp" required>
                </div>
                <div class="col-6">
                    <input type="number" name="rows" class="form-control" placeholder="Số hàng" min="1" required>
                </div>
                <div class="col-6">
                    <input type="number" name="cols" class="form-control" placeholder="Số cột" min="1" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success w-100">Tạo</button>
                </div>
            </form>
        </section>




        
        <section class="mb-4">
            <h3 class="h5">Quản lý hạng ghế</h3>
    
    <?php if (isset($editCategory) && is_array($editCategory)): ?>
        <div class="mb-3">
            <h4 class="h6">Chỉnh sửa hạng ghế</h4>
            <form method="post" class="row g-2">
                <input type="hidden" name="type" value="category_update">
                <input type="hidden" name="category_id" value="<?= htmlspecialchars($editCategory['category_id']) ?>">
                <div class="col-6">
                    <input type="text" name="category_name" class="form-control" value="<?= htmlspecialchars($editCategory['category_name']) ?>" required>
                </div>
                <div class="col-4">
                    <input type="number" step="1" name="category_price" class="form-control" value="<?= htmlspecialchars($editCategory['base_price'] ?? $editCategory['price'] ?? '') ?>" min="0" required>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Lưu</button>
                    <a href="index.php?pg=admin-theater-seat" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
            
            <form method="post" class="row g-3 mb-3">
                <input type="hidden" name="type" value="category_add">
                <div class="col-12">
                    <input type="text" name="category_name" class="form-control" placeholder="Tên hạng ghế" required>
                </div>
                <div class="col-12">
                    <input type="number" step="1" name="category_price" class="form-control" placeholder="Giá cơ bản" min="0" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success w-100">Thêm hạng ghế</button>
                </div>
            </form>
            
            <?php if (!empty($categories)): ?>
                <div class="table-responsive" style="max-height:200px; overflow-y:auto;">
                    <table class="table table-dark table-striped table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên hạng</th>
                                <th>Giá</th>
                                <th>Màu</th>
                                <th class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $c): ?>
                                <tr>
                                    <td><?= htmlspecialchars($c['category_id']) ?></td>
                                    <td><?= htmlspecialchars($c['category_name']) ?></td>
                                    <td><?= htmlspecialchars($c['base_price'] ?? ($c['price'] ?? '')) ?></td>
                                    <td>
                                        
                                        <?php
                                            $swatchColor = $c['color_class'] ?? '';
                                            $swatchStyle = '';
                                            $swatchClass = '';
                                            if (preg_match('/^[0-9a-fA-F]{6}$/', $swatchColor)) {
                                                $swatchStyle = 'background-color:#' . htmlspecialchars($swatchColor) . ';';
                                            } else {
                                                $swatchClass = htmlspecialchars($swatchColor);
                                            }
                                        ?>
                                        <span class="d-inline-block rounded seat <?= $swatchClass ?>" style="width:20px; height:20px; <?= $swatchStyle ?>"></span>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        
                                        $disabled = empty($c['can_delete']) || !$c['can_delete'];
                                        ?>
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="index.php?pg=admin-theater-seat&edit_cid=<?= $c['category_id'] ?>" class="btn btn-sm btn-primary" <?= $disabled ? 'style="pointer-events:none; opacity:0.5;"' : '' ?>>Chỉnh sửa</a>
                                            <form method="post" class="d-inline"
                                                  onsubmit="return <?= $disabled ? 'false' : 'confirm(\'Xóa hạng ghế này?\')' ?>;">
                                                <input type="hidden" name="type" value="category_delete">
                                                <input type="hidden" name="category_id" value="<?= $c['category_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" <?= $disabled ? 'disabled' : '' ?>>Xóa</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="small text-muted">Chưa có hạng ghế.</p>
            <?php endif; ?>
        </section>




       
        <?php if (isset($editTheater) && is_array($editTheater)): ?>
        <section class="mb-4">
            <h3 class="h5">Chỉnh sửa rạp và quản lý kích thước</h3>
            <div class="mb-3">
               
                <form method="post" class="row g-2 mb-2">
                    <input type="hidden" name="type" value="theater_modify">
                    <input type="hidden" name="theater_id" value="<?= htmlspecialchars($editTheater['theater_id']) ?>">
                    <div class="col-12">
                        <label class="form-label small">Tên rạp</label>
                        <input type="text" name="theater_name" class="form-control" value="<?= htmlspecialchars($editTheater['name'] ?? ($editTheater['theater_name'] ?? '')) ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Số lượng cột thêm</label>
                        <input type="number" name="add_cols" class="form-control" value="0" step="1">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Số lượng hàng thêm</label>
                        <input type="number" name="add_rows" class="form-control" value="0" step="1">
                    </div>
                    <div class="col-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                        <a href="index.php?pg=admin-theater-seat" class="btn btn-secondary">Hủy</a>
                    </div>
                </form>
               
                <form method="post" onsubmit="return confirm('Xác nhận phê duyệt rạp này?');">
                    <input type="hidden" name="type" value="theater_approve">
                    <input type="hidden" name="theater_id" value="<?= htmlspecialchars($editTheater['theater_id']) ?>">
                    <button type="submit" class="btn btn-success">Phê duyệt rạp</button>
                </form>
            </div>
        </section>
        <?php endif; ?>



        <section class="mb-4">
            <h3 class="h5">Quản lý ghế</h3>
            <?php if (!empty($selectedTheater)): ?>
                <form method="post" class="row g-2 align-items-end">
                    <input type="hidden" name="type" value="seat_update">
                    <input type="hidden" name="theater_id" value="<?= $selectedTheater ?>">
                    
                    <div class="col-12">
                        <label class="form-label small">Hạng ghế</label>
                        <select name="category_id" class="form-select" required>
                            <option value="0">-- Không áp dụng --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                   
                    <div class="col-4">
                        <label class="form-label small">Hàng</label>
                        <select name="row_char" class="form-select" required>
                            <?php
                            
                            $rowsSet = [];
                            foreach ($seatsForMap as $s) {
                                $rowsSet[$s['row_char']] = true;
                            }
                            $rowChars = array_keys($rowsSet);
                            sort($rowChars);
                            foreach ($rowChars as $r): ?>
                                <option value="<?= $r ?>"><?= $r ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-4">
                        <label class="form-label small">Ghế bắt đầu</label>
                        <select name="start_seat" class="form-select" required>
                            <?php
                            
                            $seatNums = [];
                            foreach ($seatsForMap as $s) {
                                $seatNums[$s['seat_number']] = true;
                            }
                            $nums = array_keys($seatNums);
                            sort($nums);
                            foreach ($nums as $n): ?>
                                <option value="<?= $n ?>"><?= $n ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
              
                    <div class="col-4">
                        <label class="form-label small">Ghế kết thúc</label>
                        <select name="end_seat" class="form-select" required>
                            <?php foreach ($nums as $n): ?>
                                <option value="<?= $n ?>"><?= $n ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-warning w-100">Đổi</button>
                    </div>
                </form>
            <?php else: ?>
                <p class="small text-muted">Chọn một rạp để quản lý ghế.</p>
            <?php endif; ?>
        </section>




    </div>




 
    <div class="col-12 col-md-8">
        <section class="mb-4">
            <?php if (!empty($selectedTheater)): ?>
                <?php
                
                $selectedName = '';
                foreach ($theaters as $th) {
                    $tid = $th['theater_id'] ?? ($th['id'] ?? 0);
                    if ((int)$tid === (int)$selectedTheater) {
                        $selectedName = $th['name'] ?? ($th['theater_name'] ?? '');
                        break;
                    }
                }
                ?>
                <h3 class="h5">Sơ đồ ghế cho rạp <?= htmlspecialchars($selectedName ?: ('ID ' . $selectedTheater)) ?></h3>
                <?php
                
                $rowSet   = [];
                $maxSeat  = 0;
                $seatMap  = [];
                foreach ($seatsForMap as $s) {
                    $row = $s['row_char'];
                    $num = (int)$s['seat_number'];
                    $rowSet[$row] = true;
                    if ($num > $maxSeat) $maxSeat = $num;
                    
                    if (!isset($seatMap[$row])) {
                        $seatMap[$row] = [];
                    }
                    $seatMap[$row][$num] = $s;
                }
              
                $rowChars = array_keys($rowSet);
                sort($rowChars);
                ?>
                <?php if (!empty($rowChars) && $maxSeat > 0): ?>
                    <div class="d-inline-block border border-secondary rounded p-3 bg-dark">
                        <?php foreach ($rowChars as $rowChar): ?>
                            <div class="d-flex align-items-center mb-1">
                               
                                <div class="me-2 text-warning" style="min-width:1.5rem;">
                                    <?= htmlspecialchars($rowChar) ?>
                                </div>
                                <?php for ($n = 1; $n <= $maxSeat; $n++):
                                    $seatItem = $seatMap[$rowChar][$n] ?? null;
                                    
                                    $cls = $seatItem['color_class'] ?? '';
                                   
                                    if ($seatItem !== null && !empty($seatItem['category_id'])) {
                                        $num = (int)$seatItem['real_seat_number'];
                                    } else {
                                        $num = '';
                                    }
                                ?>
                                    <div class="seat <?= htmlspecialchars($cls) ?>" style="pointer-events:none;"><?= htmlspecialchars($num) ?></div>
                                <?php endfor; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="d-flex align-items-center mt-2">
                            <div class="me-2" style="min-width:1.5rem;"></div>
                            <?php for ($n = 1; $n <= $maxSeat; $n++): ?>
                                <div class="text-muted" style="width:32px; font-size:0.75rem; text-align:center;"><?= $n ?></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Rạp này chưa có sơ đồ ghế.</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-muted">Chưa có rạp nào được chọn.</p>
            <?php endif; ?>
        </section>


        <section class="mb-4">
            <h3 class="h5">Danh sách rạp</h3>
            <?php if (!empty($theaters)): ?>
                <div class="table-responsive">
                    <table class="table table-dark table-striped table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên rạp</th>
                                <th>Sức chứa</th>
                    <th class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($theaters as $th): ?>
                                <tr>
                                    <td><?= htmlspecialchars($th['theater_id']) ?></td>
                                    <td><?= htmlspecialchars($th['name'] ?? ($th['theater_name'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars($th['total_seats'] ?? ($th['capacity'] ?? '')) ?></td>
                                    <td class="text-center">
                                        <?php
                                        
                                        $theatreDeleteDisabled = empty($th['can_delete']) || !$th['can_delete'];
                                        ?>
                                        <div class="d-flex justify-content-center gap-2">
                                            
                                            <a href="index.php?pg=admin-theater-seat&tid=<?= $th['theater_id'] ?>&edit_tid=<?= $th['theater_id'] ?>" class="btn btn-sm btn-outline-warning">Sơ đồ</a>
                                            
                                            <form method="post" class="d-inline"
                                                  onsubmit="return <?= $theatreDeleteDisabled ? 'false' : 'confirm(\'Xóa rạp này?\')' ?>;">
                                                <input type="hidden" name="type" value="theater_delete">
                                                <input type="hidden" name="theater_id" value="<?= $th['theater_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" <?= $theatreDeleteDisabled ? 'disabled' : '' ?>>Xóa</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="small text-muted">Chưa có rạp nào.</p>
            <?php endif; ?>
        </section>
    </div>
</div>