<?php
namespace App\Controllers;


use App\Models\Show;


/**
 * Controller for the home page and show listing.  Displays a carousel
 * of current shows and a grid of upcoming performances.
 */
class HomeController extends BaseController
{
    public function index(): void
    {
        // Do not auto logout administrators when visiting the home page.  Users
        // remain logged in so they can navigate seamlessly between the
        // public site and the admin area.  Any role-based restrictions
        // should be enforced via middleware in the respective controllers.
        $showModel = new Show();
        $seatCatModel = new \App\Models\SeatCategory();
        $shows = $showModel->all();
        $seatCats = $seatCatModel->all();
        $today = date('Y-m-d');
        // Arrays to collect upcoming (sắp ra mắt) and selling (đang mở bán/đang chiếu) shows
        $upcoming = [];
        $selling  = [];
        // Compute nearest performance date and price range for each show.
        // Instantiate review model once for computing average ratings
        $reviewModel = new \App\Models\Review();
        foreach ($shows as &$s) {
            $performances = $showModel->performances($s['show_id']);
            $nearestDate = null;
            $lowestPrice = null;
            $highestPrice = null;
            if ($performances) {
                foreach ($performances as $p) {
                    $perfDate = $p['performance_date'];
                    $perfPrice = (float)$p['price'];
                    // Determine next upcoming performance date
                    if ($perfDate >= $today) {
                        if ($nearestDate === null || strcmp($perfDate, $nearestDate) < 0) {
                            $nearestDate = $perfDate;
                        }
                    }
                    // Compute price range across seat categories
                    foreach ($seatCats as $cat) {
                        $price = $perfPrice + (float)$cat['base_price'];
                        if ($lowestPrice === null || $price < $lowestPrice) {
                            $lowestPrice = $price;
                        }
                        if ($highestPrice === null || $price > $highestPrice) {
                            $highestPrice = $price;
                        }
                    }
                }
            }
            $s['nearest_date'] = $nearestDate;
            $s['price_from']   = $lowestPrice;
            $s['price_to']     = $highestPrice;
            // Compute average rating for each show
            $avg = $reviewModel->getAverageRatingByShow($s['show_id']);
            $s['avg_rating'] = $avg;
            // Determine whether the show should appear in the "Sắp ra mắt" carousel.
            // Only shows explicitly marked as "Sắp chiếu" are considered upcoming.
            if (($s['status'] ?? '') === 'Sắp chiếu') {
                $upcoming[] = $s;
            }
            // Determine if the show should be included in the selling list.  A show
            // is considered available only when it has at least one performance
            // whose status is "Đang mở bán".  This avoids displaying shows that
            // have no performances or only closed performances in the "Đang mở bán"
            // section.
            $hasOpenPerf = false;
            if ($performances) {
                foreach ($performances as $p) {
                    if (($p['status'] ?? '') === 'Đang mở bán') {
                        $hasOpenPerf = true;
                        break;
                    }
                }
            }
            if ($hasOpenPerf) {
                $selling[] = $s;
            }
        }
        unset($s);
        // Sort upcoming shows by creation date descending so that the newest plays appear first.
        // In the lighter version we do not fall back to recently created shows when no
        // upcoming shows exist.  Only shows explicitly marked as "Sắp chiếu" will be
        // displayed in the upcoming section.  If the list is empty, the view will
        // simply show the "Không có vở diễn sắp ra mắt" message.
        if (!empty($upcoming)) {
            usort($upcoming, function ($a, $b) {
                return strcmp($b['created_at'], $a['created_at']);
            });
        }
        // Sort selling shows by created date so that newer plays appear first
        usort($selling, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });
        // Select a few shows to feature in the hero slider.  Only include shows
        // that currently have at least one performance with status "Đang mở bán".
        // Sort these candidate shows by created date descending (most recently added)
        // and pick the top three.  If there are fewer than three matches, the
        // slider will display the available ones without fallback.
        $heroCandidates = [];
        foreach ($shows as $show) {
            $performances = $showModel->performances($show['show_id']);
            foreach ($performances as $p) {
                if (($p['status'] ?? '') === 'Đang mở bán') {
                    $heroCandidates[$show['show_id']] = $show;
                    break;
                }
            }
        }
        usort($heroCandidates, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });
        $hero = array_slice($heroCandidates, 0, 3);
        // Fetch up to 15 of the most recent reviews for display on the home page.
        $reviewModel = new \App\Models\Review();
        $latestReviews = $reviewModel->getLatest(15);
        $this->render('home', [
            'upcomingShows' => $upcoming,
            'sellingShows'  => $selling,
            'heroShows'     => $hero,
            'latestReviews' => $latestReviews
        ]);
    }
}

