<?php
session_start();
include "db_connect.php";
include "function.php";

// Include the Header (Menu will adjust automatically based on login status)
include "header.php"; 
?>

<style>
    /* FAQ Container */
    .faq-container {
        max-width: 800px;
        margin: 0 auto;
        padding-bottom: 80px;
    }

    .page-title {
        text-align: center;
        margin-bottom: 30px;
        color: #333;
    }

    .page-intro {
        text-align: center;
        margin-bottom: 40px;
        color: #666;
    }

    /* FAQ Item Styling */
    .faq-item {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin-bottom: 15px;
        background-color: white;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }

    /* The clickable question part */
    .faq-question {
        padding: 20px;
        cursor: pointer;
        background-color: #fff;
        font-weight: bold;
        color: #005A9C; /* Primary Blue */
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 1.05rem;
        user-select: none;
    }

    .faq-question:hover {
        background-color: #f8f9fa;
    }

    /* The icon rotation animation */
    .faq-icon {
        transition: transform 0.3s ease;
        color: #005A9C;
    }

    /* The hidden answer part */
    .faq-answer {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
        background-color: #fafafa;
        color: #555;
        line-height: 1.6;
    }

    .faq-answer p {
        padding: 20px;
        margin: 0;
        border-top: 1px solid #eee;
    }

    /* Active State (When opened) */
    .faq-item.active {
        border-color: #005A9C;
    }

    .faq-item.active .faq-icon {
        transform: rotate(180deg);
    }

    .faq-item.active .faq-answer {
        max-height: 300px; /* Arbitrary height large enough for content */
    }
</style>

<div class="faq-container">
    
    <h2 class="page-title">Frequently Asked Questions</h2>
    <p class="page-intro">Find answers to common questions about the U-Transport System.</p>

    <div class="faq-item">
        <div class="faq-question">
            What is the U-Transport System?
            <i class="fa-solid fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-answer">
            <p>
                U-Transport is a web-based platform designed exclusively for MMU Malacca students. 
                It allows students to search for rides offered by other students (drivers) within the campus community, 
                providing a safer and more centralized alternative to social media groups.
            </p>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            Who can use this system?
            <i class="fa-solid fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-answer">
            <p>
                Access is strictly limited to <strong>registered students and staff of MMU Malacca Campus</strong>. 
                You must have a valid university email address to register and login. Students from other campuses cannot access this system.
            </p>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            Is this service safe?
            <i class="fa-solid fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-answer">
            <p>
                Yes. Safety is our priority. All drivers must undergo a <strong>manual verification process</strong> 
                by the admin, submitting their Student ID and Driving License before they can post rides. 
                Additionally, the passenger review system helps maintain high service standards.
            </p>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            How do I make a payment?
            <i class="fa-solid fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-answer">
            <p>
                The system does not process online payments. All payments are made <strong>offline directly to the driver</strong>. 
                You can pay via Cash or DuitNow QR transfer as agreed upon with your driver.
            </p>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            Is there a mobile app I can download?
            <i class="fa-solid fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-answer">
            <p>
                No. U-Transport is a web-based system accessible through a modern web browser (Chrome, Safari, etc.) 
                on PC or Laptop. There is no native app on the App Store or Play Store.
            </p>
        </div>
    </div>

    <div class="faq-item">
        <div class="faq-question">
            Can I track the driver's location in real-time?
            <i class="fa-solid fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-answer">
            <p>
                No, the system does not support real-time GPS tracking. However, once a booking is confirmed, 
                you will receive the driver's contact number to coordinate the pickup location directly.
            </p>
        </div>
    </div>

    <div style="text-align: center; margin-top: 40px; padding: 20px; background-color: #e7f3fe; border-radius: 10px;">
        <h3 style="margin-bottom: 10px; color: #005A9C;">Still have questions?</h3>
        <p style="margin-bottom: 20px;">We are here to help!</p>
        <a href="contact_us.php" style="background-color: #005A9C; color: white; padding: 10px 25px; text-decoration: none; border-radius: 50px; font-weight: bold;">Contact Support</a>
    </div>

</div>

<script>
    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', () => {
            // Close other open items (Optional - remove this block if you want multiple open)
            faqItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                }
            });

            // Toggle current item
            item.classList.toggle('active');
        });
    });
</script>

<?php include "footer.php"; ?>