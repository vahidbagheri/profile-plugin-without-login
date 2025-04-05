<?php
/*
Plugin Name: IP Floating Profile Advanced
Description: پروفایل IP با چند کد رهگیری، نمایش اطلاعات تمام‌صفحه، جدول ریسپانسیو لینک‌دار.
Version: 1.1
Author: شما :)
*/

// ذخیره اطلاعات کاربر
add_action('init', 'ipfp_track_user_profile');
function ipfp_track_user_profile() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $profiles = get_option('ipfp_profiles', []);

    if (!isset($profiles[$ip])) {
        $profiles[$ip] = [
            'ip' => $ip,
            'first_visit' => current_time('mysql'),
            'visit_count' => 1,
            'visited_pages' => [$_SERVER['REQUEST_URI']],
            'tracking_codes' => [],
        ];
    } else {
        $profiles[$ip]['visit_count'] += 1;
        $profiles[$ip]['visited_pages'][] = $_SERVER['REQUEST_URI'];
        $profiles[$ip]['visited_pages'] = array_unique($profiles[$ip]['visited_pages']);
    }

    // ذخیره چند کد رهگیری
    if (isset($_GET['tracking_code'])) {
        $code = sanitize_text_field($_GET['tracking_code']);
        if (!in_array($code, $profiles[$ip]['tracking_codes'])) {
            $profiles[$ip]['tracking_codes'][] = $code;
        }
    }

    update_option('ipfp_profiles', $profiles);
}

// نمایش دکمه شناور و مدال اطلاعات
add_action('wp_footer', 'ipfp_render_floating_button');
function ipfp_render_floating_button() {
    $profile = ipfp_get_user_profile();
    ?>
    <style>
        #ipfp-widget {
            position: fixed;
            top: 100px;
            left: 20px;
            z-index: 9999;
            cursor: move;
        }

        #ipfp-button {
            width: 50px;
            height: 50px;
            background: #333;
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 24px;
            user-select: none;
        }

        #ipfp-modal {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0,0,0,0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }

        #ipfp-modal-content {
            background: #fff;
            padding: 30px;
            max-width: 90%;
            max-height: 90%;
            overflow-y: auto;
            border-radius: 12px;
            box-shadow: 0 0 30px rgba(0,0,0,0.3);
            direction: rtl;
        }

        #ipfp-close {
            float: left;
            font-size: 20px;
            cursor: pointer;
            color: red;
        }

        .ipfp-table {
            width: 100%;
            border-collapse: collapse;
        }

        .ipfp-table td, .ipfp-table th {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .ipfp-table th {
            background-color: #f4f4f4;
        }

        @media (max-width: 768px) {
            .ipfp-table td, .ipfp-table th {
                font-size: 13px;
                padding: 6px;
            }
        }
    </style>

    <div id="ipfp-widget">
        <div id="ipfp-button" title="نمایش پروفایل">☰</div>
    </div>

    <div id="ipfp-modal">
        <div id="ipfp-modal-content">
            <div id="ipfp-close">✖ بستن</div>
            <h3>پروفایل شما:</h3>
            <?php echo $profile; ?>
        </div>
    </div>

    <script>
        const btn = document.getElementById('ipfp-button');
        const modal = document.getElementById('ipfp-modal');
        const closeBtn = document.getElementById('ipfp-close');

        btn.addEventListener('click', () => modal.style.display = 'flex');
        closeBtn.addEventListener('click', () => modal.style.display = 'none');

        // Drag
        const widget = document.getElementById('ipfp-widget');
        let isDragging = false, offsetX, offsetY;

        widget.addEventListener('mousedown', function(e) {
            isDragging = true;
            offsetX = e.clientX - widget.offsetLeft;
            offsetY = e.clientY - widget.offsetTop;
        });

        document.addEventListener('mousemove', function(e) {
            if (isDragging) {
                widget.style.left = (e.clientX - offsetX) + 'px';
                widget.style.top = (e.clientY - offsetY) + 'px';
            }
        });

        document.addEventListener('mouseup', function() {
            isDragging = false;
        });
    </script>
    <?php
}

// اطلاعات پروفایل برای کاربر فعلی
function ipfp_get_user_profile() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $profiles = get_option('ipfp_profiles', []);
    if (!isset($profiles[$ip])) return 'اطلاعاتی یافت نشد.';

    $profile = $profiles[$ip];
    ob_start();
    ?>
    <table class="ipfp-table">
        <tr><th>IP</th><td><?php echo esc_html($profile['ip']); ?></td></tr>
        <tr><th>اولین بازدید</th><td><?php echo esc_html($profile['first_visit']); ?></td></tr>
        <tr><th>تعداد بازدید</th><td><?php echo esc_html($profile['visit_count']); ?></td></tr>
        <tr><th>کدهای رهگیری</th>
            <td>
                <?php echo !empty($profile['tracking_codes']) ? implode(', ', array_map('esc_html', $profile['tracking_codes'])) : '-'; ?>
            </td>
        </tr>
        <tr><th>صفحات دیده‌شده</th>
            <td>
                <table style="width:100%;">
                    <?php foreach ($profile['visited_pages'] as $url): ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url($url); ?>" target="_blank">
                                    <?php echo esc_html(rawurldecode($url)); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        </tr>
    </table>
    <?php
    return ob_get_clean();
}
