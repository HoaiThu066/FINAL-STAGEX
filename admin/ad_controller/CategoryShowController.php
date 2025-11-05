<?php
/**
 * Controller for managing genres and shows in the admin area.
 *
 * Administrators can create and delete genres, add new shows and
 * assign genres to shows.  All operations are performed via
 * standard POST requests.  On completion the user is redirected
 * back to the listing page.
 */
namespace App\Controllers;

class CategoryShowController extends AdBaseController
{
    /**
     * Display and handle actions related to genres and shows.
     */
    public function index(): void
    {
        if (!$this->ensureAdmin()) return;
        // Instantiate models for genres and shows
        $genreModel = new \App\Models\Genre();
        $showModel  = new \App\Models\Show();
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['type'] ?? '';
            // Add a new genre
            if ($type === 'genre_add') {
                $name = trim($_POST['genre_name'] ?? '');
                if ($name) {
                    if ($genreModel->create($name)) {
                        $_SESSION['success'] = 'Thể loại được thêm thành công.';
                    } else {
                        $_SESSION['error'] = 'Không thể thêm thể loại.';
                    }
                } else {
                    $_SESSION['error'] = 'Tên thể loại không được bỏ trống.';
                }
                $this->redirect('index.php?pg=admin-category-show');
                return;
            }
            // Update an existing genre
            if ($type === 'genre_update') {
                $id   = (int)($_POST['genre_id'] ?? 0);
                $name = trim($_POST['genre_name'] ?? '');
                if ($id > 0 && $name) {
                    if ($genreModel->update($id, $name)) {
                        $_SESSION['success'] = 'Đã cập nhật thể loại.';
                    } else {
                        $_SESSION['error'] = 'Không thể cập nhật thể loại.';
                    }
                } else {
                    $_SESSION['error'] = 'Vui lòng nhập tên thể loại.';
                }
                $this->redirect('index.php?pg=admin-category-show');
                return;
            }
            // Delete a genre
            if ($type === 'genre_delete') {
                $id = (int)($_POST['genre_id'] ?? 0);
                if ($id > 0) {
                    if ($genreModel->delete($id)) {
                        $_SESSION['success'] = 'Đã xóa thể loại.';
                    } else {
                        $_SESSION['error'] = 'Không thể xóa thể loại.';
                    }
                }
                $this->redirect('index.php?pg=admin-category-show');
                return;
            }
            // Add a new show
            if ($type === 'show_add') {
                $title    = trim($_POST['title'] ?? '');
                $desc     = trim($_POST['description'] ?? '');
                $duration = (int)($_POST['duration'] ?? 0);
                $director = trim($_POST['director'] ?? '');
                $poster   = trim($_POST['poster_url'] ?? '');
                // Always set new shows to the Vietnamese "Sắp chiếu" status.  The
                // status cannot be chosen manually as it is determined by the
                // existence and statuses of performances.  See proc_update_show_statuses().
                $status   = 'Sắp chiếu';
                $genreIds = $_POST['genre_ids'] ?? [];
                if ($title && $duration > 0 && $director && $poster) {
                    $newId = $showModel->create($title, $desc, $duration, $director, $poster, $status);
                    if ($newId) {
                        $pdo = \App\Models\Database::connect();
                        $stmt = $pdo->prepare('INSERT INTO show_genres (show_id, genre_id) VALUES (:sid, :gid)');
                        foreach ($genreIds as $gid) {
                            $stmt->execute(['sid' => $newId, 'gid' => $gid]);
                        }
                        $_SESSION['success'] = 'Đã thêm vở diễn mới.';
                    } else {
                        $_SESSION['error'] = 'Không thể thêm vở diễn.';
                    }
                } else {
                    $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin vở diễn.';
                }
                $this->redirect('index.php?pg=admin-category-show');
                return;
            }
            // Delete a show
            if ($type === 'show_delete') {
                $id = (int)($_POST['show_id'] ?? 0);
                if ($id > 0) {
                    if ($showModel->delete($id)) {
                        $_SESSION['success'] = 'Đã xóa vở diễn.';
                    } else {
                        $_SESSION['error'] = 'Không thể xóa vở diễn.';
                    }
                }
                $this->redirect('index.php?pg=admin-category-show');
                return;
            }

            // Update an existing show
            if ($type === 'show_update') {
                $showId    = (int)($_POST['show_id'] ?? 0);
                $title     = trim($_POST['title'] ?? '');
                $desc      = trim($_POST['description'] ?? '');
                $duration  = (int)($_POST['duration'] ?? 0);
                $director  = trim($_POST['director'] ?? '');
                $poster    = trim($_POST['poster_url'] ?? '');
                // Do not allow manual changes to show status.  The status is derived
                // from performance data via proc_update_show_statuses() and the
                // fallback update in Show::all().  Ignore any posted status value.
                $genreIds  = $_POST['genre_ids'] ?? [];
                if ($showId > 0 && $title && $duration > 0 && $director && $poster) {
                    $pdo = \App\Models\Database::connect();
                    // Update the show record.  Exclude the status column so that it
                    // remains unchanged; it will be recalculated automatically.
                    $stmt = $pdo->prepare('UPDATE shows SET title = :title, description = :desc, duration_minutes = :dur, director = :dir, poster_image_url = :poster WHERE show_id = :sid');
                    $stmt->execute([
                        'title'  => $title,
                        'desc'   => $desc,
                        'dur'    => $duration,
                        'dir'    => $director,
                        'poster' => $poster,
                        'sid'    => $showId
                    ]);
                    // Update genre pivot table: remove existing and insert new selections
                    $stmtDel = $pdo->prepare('DELETE FROM show_genres WHERE show_id = :sid');
                    $stmtDel->execute(['sid' => $showId]);
                    $stmtIns = $pdo->prepare('INSERT INTO show_genres (show_id, genre_id) VALUES (:sid, :gid)');
                    foreach ($genreIds as $gid) {
                        $stmtIns->execute(['sid' => $showId, 'gid' => $gid]);
                    }
                    $_SESSION['success'] = 'Đã cập nhật vở diễn.';
                } else {
                    $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin vở diễn.';
                }
                $this->redirect('index.php?pg=admin-category-show');
                return;
            }
        }
        // Fetch data for display
        $genres = $genreModel->all();
        $shows  = $showModel->all();
        usort($genres, function ($a, $b) {
            return ($a['genre_id'] ?? 0) <=> ($b['genre_id'] ?? 0);
        });
        usort($shows, function ($a, $b) {
            return ($a['show_id'] ?? 0) <=> ($b['show_id'] ?? 0);
        });
        // For each show, determine whether it can be deleted.  A show can
        // only be deleted when it has no associated performances.  This
        // property is used by the view to enable or disable the delete
        // button.  When the stored procedures are unavailable the
        // fallback method Show::performances() will still return the
        // scheduled performances for that show.
        // Determine whether each show can be deleted.  A show can only be
        // deleted if it has no performances at all (regardless of
        // performance status).  Query the performances table directly
        // because Show::performances() only returns open performances.
        $pdo = \App\Models\Database::connect();
        foreach ($shows as &$sh) {
            $sid = (int)($sh['show_id'] ?? 0);
            // Use a stored procedure to count performances for a show
            $stmt = $pdo->prepare('CALL proc_count_performances_by_show(:sid)');
            $stmt->execute(['sid' => $sid]);
            $count = 0;
            if ($row = $stmt->fetch()) {
                $count = (int)($row['performance_count'] ?? 0);
            }
            $stmt->closeCursor();
            $sh['can_delete'] = ($count === 0);
        }
        unset($sh);

        // Determine if editing an existing show
        $editShow = null;
        $selectedGenres = [];
        if (isset($_GET['edit_id'])) {
            $editId = (int)$_GET['edit_id'];
            if ($editId > 0) {
                $editShow = $showModel->find($editId);
                if ($editShow) {
                    $pdo = \App\Models\Database::connect();
                    // Use a stored procedure to get genre IDs for a show
                    $stmt = $pdo->prepare('CALL proc_get_genre_ids_by_show(:sid)');
                    $stmt->execute(['sid' => $editId]);
                    $selectedGenres = [];
                    foreach ($stmt->fetchAll() as $row) {
                        $selectedGenres[] = (int)($row['genre_id'] ?? 0);
                    }
                    $stmt->closeCursor();
                }
            }
        }

        // Determine if editing an existing genre
        $editGenre = null;
        if (isset($_GET['edit_genre_id'])) {
            $gid = (int)$_GET['edit_genre_id'];
            if ($gid > 0) {
                // Find the genre by iterating through the loaded genres array
                foreach ($genres as $g) {
                    if ((int)($g['genre_id'] ?? 0) === $gid) {
                        $editGenre = $g;
                        break;
                    }
                }
            }
        }
        // Render the view outside of its former folder.  The index file has
        // been moved and renamed to ad_category&show.php for clarity.
        $this->renderAdmin('ad_category&show', [
            'genres'        => $genres,
            'shows'         => $shows,
            'editShow'      => $editShow,
            'selectedGenres'=> $selectedGenres
            ,'editGenre'     => $editGenre
        ]);
    }
}