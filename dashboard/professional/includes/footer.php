<!-- Start of footer - page content ends here -->
        </main><!-- End of main-content -->
    </div><!-- End of dashboard-container -->
    
    <!-- Common JavaScript -->
    <script>
        // Handle dropdown menus
        document.addEventListener('DOMContentLoaded', function() {
            // Notification dropdown toggle
            const notificationsButton = document.querySelector('.notifications-dropdown button');
            if (notificationsButton) {
                notificationsButton.addEventListener('click', function() {
                    // This would fetch and display notifications
                    console.log('Notifications clicked');
                });
            }
            
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            if (alerts.length > 0) {
                setTimeout(function() {
                    alerts.forEach(function(alert) {
                        alert.style.opacity = '0';
                        setTimeout(function() {
                            alert.style.display = 'none';
                        }, 300);
                    });
                }, 5000);
            }
        });
    </script>
    
    <!-- Include any page-specific scripts -->
    <?php if (isset($page_specific_scripts)): ?>
        <?php echo $page_specific_scripts; ?>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar toggle functionality
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Store sidebar state in cookie for persistence
            const isCollapsed = sidebar.classList.contains('collapsed');
            document.cookie = `sidebar_collapsed=${isCollapsed}; path=/; max-age=31536000`;
        });
        
        // Initialize any dropdown menus
        const userDropdown = document.querySelector('.user-dropdown');
        if (userDropdown) {
            userDropdown.addEventListener('click', function() {
                this.classList.toggle('active');
            });
        }
        
        const notificationIcon = document.querySelector('.notification-icon');
        if (notificationIcon) {
            notificationIcon.addEventListener('click', function() {
                const dropdown = this.parentElement;
                dropdown.classList.toggle('active');
            });
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.user-dropdown') && !event.target.closest('.notification-dropdown')) {
                document.querySelectorAll('.user-dropdown.active, .notification-dropdown.active').forEach(el => {
                    el.classList.remove('active');
                });
            }
        });
    });
    
    // Include any page-specific JavaScript
    <?php if (isset($page_js)): ?>
    <?php echo $page_js; ?>
    <?php endif; ?>
    </script>
</body>
</html>
