        </main>
        <footer class="admin-footer">
            <p>&copy; <?php echo date('Y'); ?> PUP Biñan Campus Admin Panel</p>
        </footer>
    </div>
</div>
<script>
    // Check if user is on mobile device and redirect
    (function() {
        function checkMobile() {
            if (window.innerWidth < 768) {
                alert('The admin panel is not available on mobile. Please use a laptop or desktop.');
                window.location.href = '/';
            }
        }
        
        // Check on page load
        checkMobile();
        
        // Check on window resize
        window.addEventListener('resize', checkMobile);
    })();
</script>
</body>
</html>
