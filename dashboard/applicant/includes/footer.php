            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Store sidebar state in cookie for persistence
            const isCollapsed = sidebar.classList.contains('collapsed');
            document.cookie = `sidebar_collapsed=${isCollapsed}; path=/; max-age=31536000`;
        });

        // Notification dropdown toggle
        document.getElementById('notification-toggle').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('notification-menu').classList.toggle('show');
        });

        // Hide notification dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.notification-dropdown')) {
                document.getElementById('notification-menu').classList.remove('show');
            }
        });

        // Mark notification as read when clicked
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.dataset.id;
                if (this.classList.contains('unread')) {
                    fetch('ajax/mark_notification_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `notification_id=${notificationId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.classList.remove('unread');
                            
                            // Update notification count
                            const badge = document.querySelector('.notification-badge');
                            if (badge) {
                                const count = parseInt(badge.textContent) - 1;
                                if (count <= 0) {
                                    badge.remove();
                                } else {
                                    badge.textContent = count;
                                }
                            }
                        }
                    });
                }
            });
        });

        // Mark all notifications as read
        document.getElementById('mark-all-read').addEventListener('click', function(e) {
            e.preventDefault();
            fetch('ajax/mark_all_notifications_read.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });
                    
                    // Remove notification badge
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        badge.remove();
                    }
                }
            });
        });
    </script>
    <?php if (isset($page_specific_js)): ?>
        <?php echo $page_specific_js; ?>
    <?php endif; ?>
</body>
</html>
