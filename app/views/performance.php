<h2 class="h4 mb-3">Chọn ghế</h2>
<p><strong>Vở diễn:</strong> <?= htmlspecialchars($show['title']) ?> | <strong>Ngày:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($performance['performance_date']))) ?> | <strong>Giờ:</strong> <?= htmlspecialchars(substr($performance['start_time'],0,5)) ?> | <strong>Phòng:</strong> <?= htmlspecialchars($performance['theater_name']) ?></p>

<div class="mb-3">
    <h5 class="h6">Loại ghế và phụ thu</h5>
    <ul class="list-inline">
        <?php foreach ($categories as $c): ?>
            <?php
            // For dynamically generated colours (stored as hex without a leading '#'),
            // fall back to inline styles so that the colour displays correctly.  When
            // a colour class corresponds to a predefined Bootstrap context (e.g. 'primary',
            // 'success', etc.), it will be ignored in favour of the inline style if
            // present.  Hex colours are six hexadecimal digits without '#'.
            $catColor = $c['color_class'] ?? '';
            $style    = '';
            if (preg_match('/^[0-9a-fA-F]{6}$/', $catColor)) {
                $style = 'style="background-color:#' . htmlspecialchars($catColor) . ';"';
                $catClass = '';
            } else {
                $catClass = htmlspecialchars($catColor);
            }
            ?>
            <li class="list-inline-item me-3">
                <span class="seat <?= $catClass ?>" <?= $style ?>></span>
                <?= htmlspecialchars($c['category_name']) ?> (+<?= number_format($c['base_price'],0,',','.') ?> VND)
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<form method="post">
    <?php
    // Build the seat grid and price map.  Include all seats so that gaps
    // (seats with null category) are preserved in the layout.  Compute
    // the maximum number of physical seats per row to ensure rows are
    // aligned.  For seats without a category we assign an id of null and
    // no display number.
    $priceMap = [];
    // Organise seats by row and seat number
    $seatRowsMap = [];
    $maxSeatNum = 0;
    foreach ($seats as $s) {
        $row = $s['row_char'];
        $num = (int)$s['seat_number'];
        if ($num > $maxSeatNum) $maxSeatNum = $num;
        if (!isset($seatRowsMap[$row])) {
            $seatRowsMap[$row] = [];
        }
        $seatRowsMap[$row][$num] = $s;
        // Populate price map only for seats that can be booked (have a category)
        if ($s['category_id'] !== null) {
            $priceMap[$s['seat_id']] = (float)$performance['price'] + (float)$s['base_price'];
        }
    }
    // Sort row keys alphabetically and seat numbers ascending
    ksort($seatRowsMap);
    foreach ($seatRowsMap as &$seatList) {
        ksort($seatList);
    }
    unset($seatList);
    // Build a grid for JavaScript: each row is an array of seat objects in
    // physical seat order.  For gaps, id and num are null/empty.
    $seatGrid = [];
    foreach ($seatRowsMap as $rowChar => $seatsByNum) {
        $rowEntries = [];
        for ($n = 1; $n <= $maxSeatNum; $n++) {
            if (isset($seatsByNum[$n])) {
                $seat = $seatsByNum[$n];
                if ($seat['category_id'] !== null) {
                    $rowEntries[] = [
                        'id'     => $seat['seat_id'],
                        'num'    => $seat['real_seat_number'],
                        'class'  => $seat['color_class'],
                        'color'  => $seat['color_class'],
                        'booked' => isset($booked[$seat['seat_id']])
                    ];
                } else {
                    // Gap: preserve spacing but no seat id or number
                    $rowEntries[] = [
                        'id'     => null,
                        'num'    => '',
                        'class'  => '',
                        'color'  => '',
                        'booked' => false
                    ];
                }
            } else {
                // Should not occur as seats are pre‑generated for all positions
                $rowEntries[] = [
                    'id'     => null,
                    'num'    => '',
                    'class'  => '',
                    'color'  => '',
                    'booked' => false
                ];
            }
        }
        $seatGrid[$rowChar] = $rowEntries;
    }
    ?>
    <div id="seat-container" data-price-map='<?= json_encode([]) ?>'></div>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const container = document.getElementById('seat-container');
            // Inject price map for selectable seats
            container.setAttribute('data-price-map', '<?= json_encode($priceMap) ?>');
            let html = '<div class="d-inline-block border p-3 bg-dark rounded-3">';
            const seatGrid = <?= json_encode($seatGrid) ?>;
            const maxSeat = <?= $maxSeatNum ?>;
            for (const row in seatGrid) {
                html += `<div class="d-flex align-items-center mb-1"><div class="me-2">${row}</div>`;
                seatGrid[row].forEach(s => {
                    if (s.id) {
                        // Build classes for selectable seat
                        const classes = ['seat'];
                        if (s.class && !/^[0-9a-fA-F]{6}$/.test(s.class)) {
                            classes.push(s.class);
                        }
                        if (s.booked) classes.push('booked');
                        let style = '';
                        if (s.color && /^[0-9a-fA-F]{6}$/.test(s.color)) {
                            style = `background-color:#${s.color};`;
                        }
                        html += `<div class="${classes.join(' ')}" data-seat-id="${s.id}" style="${style}">${s.num}</div>`;
                    } else {
                        // Gap: preserve spacing but non‑interactive blank cell
                        html += '<div class="seat" style="background-color:transparent; border:none; cursor:default;"></div>';
                    }
                });
                html += '</div>';
            }
            // Render bottom seat numbers using physical seat numbers for alignment
            html += '<div class="d-flex align-items-center mt-2">';
            html += '<div class="me-2" style="min-width:1.5rem;"></div>';
            for (let n = 1; n <= maxSeat; n++) {
                html += `<div class="text-muted" style="width:32px; font-size:0.75rem; text-align:center;">${n}</div>`;
            }
            html += '</div>';
            html += '</div>';
            container.innerHTML = html;
        });
    </script>
    <input type="hidden" name="seats[]" id="selected-seats-input" value="[]">
    <div class="mt-3 d-flex justify-content-between align-items-center">
        <span>Tổng cộng: <strong id="selected-total">0&nbsp;₫</strong></span>
        <button type="submit" class="btn btn-warning">Tiếp tục</button>
    </div>
</form>