</div> </main>

    <footer style="background-color: #333; color: #fff; text-align: center; padding: 1.5rem 0; position: fixed; bottom: 0; left: 0; width: 100%; z-index: 999;">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> U-Transport System. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if(isset($_SESSION['swal_msg'])): ?>
    <script>
        Swal.fire({
            title: '<?php echo $_SESSION['swal_title']; ?>',
            text: '<?php echo $_SESSION['swal_msg']; ?>',
            icon: '<?php echo $_SESSION['swal_type']; ?>',
            confirmButtonColor: '#005A9C', // Matches your primary blue color
            confirmButtonText: 'OK',
            // Animation settings (Fade in/out)
            showClass: {
                popup: 'swal2-show',
                backdrop: 'swal2-backdrop-show',
                icon: 'swal2-icon-show'
            },
            hideClass: {
                popup: 'swal2-hide',
                backdrop: 'swal2-backdrop-hide',
                icon: 'swal2-icon-hide'
            },
            timer: 3000, // Auto close after 3 seconds (optional)
            timerProgressBar: true
        });
    </script>
    
    <?php 
        unset($_SESSION['swal_msg']); 
        unset($_SESSION['swal_type']);
        unset($_SESSION['swal_title']);
    ?>
    <?php endif; ?>

</body>
</html>