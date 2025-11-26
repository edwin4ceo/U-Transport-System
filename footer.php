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
            
            // Primary Button (Confirm)
            confirmButtonText: '<?php echo isset($_SESSION['swal_btn_text']) ? $_SESSION['swal_btn_text'] : "OK"; ?>',
            confirmButtonColor: '#005A9C',

            // Secondary Button (Cancel/Try Again) - Only show if PHP says so
            showCancelButton: <?php echo (isset($_SESSION['swal_show_cancel']) && $_SESSION['swal_show_cancel']) ? 'true' : 'false'; ?>,
            cancelButtonText: '<?php echo isset($_SESSION['swal_cancel_text']) ? $_SESSION['swal_cancel_text'] : "Cancel"; ?>',
            cancelButtonColor: '#6c757d', // Grey color for "Try Again"
            
            // Animation settings
            showClass: {
                popup: 'swal2-show',
                backdrop: 'swal2-backdrop-show',
                icon: 'swal2-icon-show'
            },
            hideClass: {
                popup: 'swal2-hide',
                backdrop: 'swal2-backdrop-hide',
                icon: 'swal2-icon-hide'
            }
        }).then((result) => {
            // Logic: If confirm button clicked AND link is set, redirect
            <?php if(isset($_SESSION['swal_btn_link'])): ?>
                if (result.isConfirmed) {
                    window.location.href = '<?php echo $_SESSION['swal_btn_link']; ?>';
                }
            <?php endif; ?>
            // If cancel button ("Try Again") is clicked, it just closes automatically.
        });
    </script>
    
    <?php 
        unset($_SESSION['swal_msg']); 
        unset($_SESSION['swal_type']);
        unset($_SESSION['swal_title']);
        unset($_SESSION['swal_btn_text']);
        unset($_SESSION['swal_btn_link']);
        unset($_SESSION['swal_show_cancel']);
        unset($_SESSION['swal_cancel_text']);
    ?>
    <?php endif; ?>

</body>
</html>